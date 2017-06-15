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

namespace plagiarism_unplag\classes;

use assign;
use coding_exception;
use context_module;
use plagiarism_unplag;
use plagiarism_unplag\classes\entities\unplag_archive;
use plagiarism_unplag\classes\helpers\unplag_check_helper;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_assign
 *
 * @package     plagiarism_unplag\classes
 * @subpackage  plagiarism
 * @namespace   plagiarism_unplag\classes
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_assign {
    const DB_NAME = 'assign';

    /**
     * @param      $cmid
     * @param null $userid
     *
     * @return bool|\stdClass
     */
    public static function get_user_submission_by_cmid($cmid, $userid = null) {
        global $USER;

        try {
            $modulecontext = context_module::instance($cmid);
            $assign = new assign($modulecontext, false, false);
        } catch (\Exception $ex) {
            return false;
        }

        return ($assign->get_user_submission(($userid !== null) ? $userid : $USER->id, false));
    }

    /**
     * @param $id
     *
     * @throws coding_exception
     */
    public static function check_submitted_assignment($id) {
        global $DB;

        $plagiarismfile = $DB->get_record(UNPLAG_FILES_TABLE, array('id' => $id), '*', MUST_EXIST);
        if (in_array($plagiarismfile->statuscode, array(UNPLAG_STATUSCODE_PROCESSED, UNPLAG_STATUSCODE_ACCEPTED))) {
            // Sanity Check.
            return;
        }

        $cm = get_coursemodule_from_id('', $plagiarismfile->cm);

        if (plagiarism_unplag::is_support_mod($cm->modname)) {

            $file = get_file_storage()->get_file_by_hash($plagiarismfile->identifier);
            if ($file->is_directory()) {
                return;
            }

            self::run_process_detection($file, $plagiarismfile);
        }
    }

    /**
     * @param int  $contextid
     * @param bool $itemid
     *
     * @return \stored_file[]
     */
    public static function get_area_files($contextid, $itemid = false) {
        return get_file_storage()->get_area_files($contextid, 'assignsubmission_file', 'submission_files', $itemid, null, false);
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public static function is_draft($id) {
        global $DB;

        $sql = 'SELECT COUNT(id) FROM {assign_submission} WHERE id = ? AND status = ?';

        return (bool) $DB->count_records_sql($sql, array($id, 'draft'));
    }

    /**
     * @param $id
     *
     * @return \stdClass
     */
    public static function get($id) {
        global $DB;

        return $DB->get_record(self::DB_NAME, array('id' => $id), '*', MUST_EXIST);
    }

    /**
     * @param integer $cmid
     *
     * @return \stdClass
     */
    public static function get_by_cmid($cmid) {
        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);

        return self::get($cm->instance);
    }

    /**
     * @param \stored_file $file
     * @param              $plagiarismfile
     */
    private static function run_process_detection(\stored_file $file, $plagiarismfile) {

        $ucore = new unplag_core($plagiarismfile->cm, $plagiarismfile->userid);

        if (plagiarism_unplag::is_archive($file)) {
            (new unplag_archive($file, $ucore))->run_checks();
        } else {
            $plagiarismentity = $ucore->get_plagiarism_entity($file);
            $internalfile = $plagiarismentity->upload_file_on_unplag_server();
            unplag_check_helper::run_plagiarism_detection($plagiarismentity, $internalfile);
        }
    }
}