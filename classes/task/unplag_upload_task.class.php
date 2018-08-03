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
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\task;

use plagiarism_unplag\classes\entities\providers\unplag_file_provider;
use plagiarism_unplag\classes\entities\unplag_archive;
use plagiarism_unplag\classes\exception\unplag_exception;
use plagiarism_unplag\classes\plagiarism\unplag_content;
use plagiarism_unplag\classes\services\storage\filesize_checker;
use plagiarism_unplag\classes\services\storage\unplag_file_state;
use plagiarism_unplag\classes\unplag_assign;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_upload_task
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_upload_task extends unplag_abstract_task {

    /**
     * Key of pathname hash data parameter
     */
    const PATHNAME_HASH = 'pathnamehash';
    /**
     * Key of ucore data parameter
     */
    const UCORE_KEY = 'ucore';

    /**
     * @var unplag_core
     */
    protected $ucore;

    /**
     * @var object
     */
    protected $internalfile;

    /**
     * Execute of adhoc task
     */
    public function execute() {
        $data = $this->get_custom_data();
        if (!is_object($data)) {
            return;
        }

        if (!property_exists($data, self::UCORE_KEY) || !property_exists($data, self::PATHNAME_HASH)) {
            return;
        }

        try {
            $modname = $this->get_modname($data->ucore);
            $this->ucore = new unplag_core($data->ucore->cmid, $data->ucore->userid, $modname);
            if ($modname == UNPLAG_MODNAME_ASSIGN
                && (bool)unplag_assign::get_by_cmid($this->ucore->cmid)->teamsubmission) {
                $this->ucore->enable_teamsubmission();
            }

            $file = get_file_storage()->get_file_by_hash($data->pathnamehash);
            $this->internalfile = $this->ucore->get_plagiarism_entity($file)->get_internal_file();

            if (!\plagiarism_unplag::is_archive($file)) {
                $this->process_single_file($file);

                return;
            }

            $maxsupportedcount = unplag_settings::get_assign_settings(
                $this->ucore->cmid,
                unplag_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT
            );

            if ($maxsupportedcount < unplag_archive::MIN_SUPPORTED_FILES_COUNT ||
                $maxsupportedcount > unplag_archive::MAX_SUPPORTED_FILES_COUNT) {
                $maxsupportedcount = unplag_archive::DEFAULT_SUPPORTED_FILES_COUNT;
            }

            $supportedcount = 0;
            $extracted = (new unplag_archive($file, $this->ucore))->extract();
            if (!count($extracted)) {
                throw new unplag_exception(unplag_exception::ARCHIVE_IS_EMPTY);
            }

            foreach ($extracted as $item) {
                if ($supportedcount >= $maxsupportedcount) {
                    unplag_archive::unlink($item['path']);
                    continue;
                }

                try {
                    $this->process_archive_item($item);
                    $supportedcount++;
                } catch (\Exception $exception) {
                    mtrace("File " . $item['filename'] . " processing error: " . $exception->getMessage());

                    continue;
                } finally {
                    unplag_archive::unlink($item['path']);
                }
            }

            if ($supportedcount < 1) {
                throw new unplag_exception(unplag_exception::ARCHIVE_IS_EMPTY);
            }
        } catch (\Exception $e) {
            if ($this->internalfile) {
                unplag_file_provider::to_error_state($this->internalfile, $e->getMessage());
            } else {
                unplag_file_provider::to_error_state_by_pathnamehash($data->pathnamehash, $e->getMessage());
            }

            mtrace("File {$data->pathnamehash}(pathnamehash) processing error: " . $e->getMessage());
        }
    }

    /**
     * Process archive item
     *
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
        $internalfile = $plagiarismentity->get_internal_file();
        if ($internalfile->state == unplag_file_state::CREATED) {
            $internalfile->state = unplag_file_state::UPLOADING;
            unplag_file_provider::save($internalfile);
            $plagiarismentity->upload_file_on_unplag_server();
        }

        unset($plagiarismentity, $content);
    }

    /**
     * Process single stored file
     *
     * @param \stored_file $file
     * @throws unplag_exception
     */
    protected function process_single_file(\stored_file $file) {
        if (filesize_checker::file_is_to_large($file)) {
            throw new unplag_exception(unplag_exception::FILE_IS_TOO_LARGE);
        }

        if ($this->internalfile->external_file_uuid) {
            mtrace("File already uploaded. Skipped. Plugin file id: {$this->internalfile->id}");

            return;
        }

        $plagiarismentity = $this->ucore->get_plagiarism_entity($file);
        $plagiarismentity->upload_file_on_unplag_server();
    }
}