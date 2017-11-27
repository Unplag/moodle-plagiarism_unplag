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
 * unplag_archive.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\entities;

use plagiarism_unplag\classes\entities\extractors\unplag_extractor_interface;
use plagiarism_unplag\classes\entities\extractors\unplag_zip_extractor;
use plagiarism_unplag\classes\exception\unplag_exception;
use plagiarism_unplag\classes\task\unplag_upload_and_check_task;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_notification;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

if (!defined('ARCHIVE_IS_EMPTY')) {
    define('ARCHIVE_IS_EMPTY', 'Archive is empty or contains document(s) with no text');
}

if (!defined('ARCHIVE_CANT_BE_OPEN')) {
    define('ARCHIVE_CANT_BE_OPEN', 'Can\'t open zip archive');
}

/**
 * Class unplag_archive
 *
 * @package   plagiarism_unplag\classes\entities
 * @namespace plagiarism_unplag\classes\entities
 *
 */
class unplag_archive {

    const DEFAULT_SUPPORTED_FILES_COUNT = 10;
    const MIN_SUPPORTED_FILES_COUNT = 1;
    const MAX_SUPPORTED_FILES_COUNT = 100;

    /**
     * ZIP_MIMETYPE
     */
    const ZIP_MIMETYPE = 'application/zip';

    /**
     * @var \stored_file
     */
    private $file;
    /**
     * @var unplag_core
     */
    private $core;

    /**
     * @var unplag_extractor_interface
     */
    private $extractor;

    /**
     * @var object
     */
    private $archive;

    /**
     * unplag_archive constructor.
     *
     * @param \stored_file $file
     * @param unplag_core  $core
     *
     * @throws unplag_exception
     */
    public function __construct(\stored_file $file, unplag_core $core) {
        $this->file = $file;
        $this->core = $core;

        $this->archive = $this->core->get_plagiarism_entity($this->file)->get_internal_file();

        switch ($file->get_mimetype()) {
            case self::ZIP_MIMETYPE:
                $this->extractor = new unplag_zip_extractor($file);
                break;
            default:
                throw new unplag_exception('Unsupported mimetype');
        }
    }

    /**
     * Extract each file
     *
     * @return \Generator
     */
    public function extract() {
        try {
            return $this->extractor->extract();
        } catch (\Exception $ex) {
            $this->invalid_response($ex->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function run_checks() {
        global $DB;

        unplag_upload_and_check_task::add_task([
            'pathnamehash' => $this->file->get_pathnamehash(),
            'ucore'        => $this->core,
        ]);

        $this->archive->statuscode = UNICHECK_STATUSCODE_ACCEPTED;
        $this->archive->errorresponse = null;
        $DB->update_record(UNICHECK_FILES_TABLE, $this->archive);

        return true;
    }

    public function restart_check() {
        global $DB;

        $internalfile = $this->core->get_plagiarism_entity($this->file)->get_internal_file();
        $childs = $DB->get_records_list(UNPLAG_FILES_TABLE, 'parent_id', [$internalfile->id]);
        if ($childs) {
            foreach ((object) $childs as $child) {
                if ($child->check_id) {
                    unplag_api::instance()->delete_check($child);
                }
            }

            unplag_notification::success('plagiarism_run_success', true);

            $this->run_checks();
        }
    }

    /**
     * @param $file
     */
    public static function unlink($file) {
        if (!unlink($file)) {
            mtrace('Error deleting ' . $file);
        }
    }

    /**
     * @param $reason
     */
    private function invalid_response($reason) {
        global $DB;

        $this->archive->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
        $this->archive->errorresponse = json_encode([
            ["message" => $reason],
        ]);

        $DB->update_record(UNPLAG_FILES_TABLE, $this->archive);
    }
}
