<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * unplag_upload_task.class.php
 *
 * @package     plagiarism_unplag
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\task;

use plagiarism_unplag\classes\entities\unplag_archive;
use plagiarism_unplag\classes\exception\unplag_exception;
use plagiarism_unplag\classes\plagiarism\unplag_content;
use plagiarism_unplag\classes\unplag_assign;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_upload_task
 *
 * @package   plagiarism_unplag\classes\task
 * @namespace plagiarism_unplag\classes\task
 *
 */
class unplag_upload_task extends unplag_abstract_task {

    const PATHNAME_HASH = 'pathnamehash';
    const UCORE_KEY = 'ucore';

    /**
     * @var unplag_core
     */
    protected $ucore;

    /**
     * @var object
     */
    protected $internalfile;

    public function execute() {
        $data = $this->get_custom_data();
        if (!is_object($data)) {
            return;
        }

        if (!property_exists($data, self::UCORE_KEY) || !property_exists($data, self::PATHNAME_HASH)) {
            return;
        }

        $this->ucore = new unplag_core($data->ucore->cmid, $data->ucore->userid);

        if ((bool) unplag_assign::get_by_cmid($this->ucore->cmid)->teamsubmission) {
            $this->ucore->enable_teamsubmission();
        }

        $file = get_file_storage()->get_file_by_hash($data->pathnamehash);
        $this->internalfile = $this->ucore->get_plagiarism_entity($file)->get_internal_file();

        if (!\plagiarism_unplag::is_archive($file)) {
            $this->process_single_file($file);
            unset($this->ucore, $file);

            return;
        }

        try {
            $maxsupportedcount = unplag_settings::get_assign_settings(
                $this->ucore->cmid,
                unplag_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT
            );

            if ($maxsupportedcount < unplag_archive::MIN_SUPPORTED_FILES_COUNT ||
                $maxsupportedcount > unplag_archive::MAX_SUPPORTED_FILES_COUNT) {
                $maxsupportedcount = unplag_archive::DEFAULT_SUPPORTED_FILES_COUNT;
            }

            $supportedcount = 0;
            foreach ((new unplag_archive($file, $this->ucore))->extract() as $item) {
                if ($supportedcount > $maxsupportedcount) {
                    unplag_archive::unlink($item['path']);
                    continue;
                }

                $this->process_archive_item($item);
                $supportedcount++;
            }

            if ($supportedcount < 1) {
                throw new unplag_exception(ARCHIVE_IS_EMPTY);
            }
        } catch (\Exception $e) {
            $this->invalid_response($e->getMessage());
            mtrace('Archive error ' . $e->getMessage());
        }

        unset($this->ucore, $file);
    }

    /**
     * Check response validation
     *
     * @param string $reason
     */
    private function invalid_response($reason) {
        global $DB;

        $this->internalfile->statuscode = UNICHECK_STATUSCODE_INVALID_RESPONSE;
        $this->internalfile->errorresponse = json_encode([
            ["message" => $reason],
        ]);

        $DB->update_record(UNICHECK_FILES_TABLE, $this->internalfile);
    }

    /**
     * @param array $item
     */
    protected function process_archive_item(array $item) {
        $content = file_get_contents($item['path']);
        $plagiarismentity = new unplag_content(
            $this->ucore,
            $content,
            $item['filename'],
            $item['format'],
            $this->internalfile->id
        );
        $plagiarismentity->get_internal_file();
        $plagiarismentity->upload_file_on_unplag_server();

        unset($plagiarismentity, $content);

        unplag_archive::unlink($item['path']);
    }

    /**
     * @param \stored_file $file
     */
    protected function process_single_file(\stored_file $file) {
        $plagiarismentity = $this->ucore->get_plagiarism_entity($file);
        $plagiarismentity->upload_file_on_unplag_server();
    }
}