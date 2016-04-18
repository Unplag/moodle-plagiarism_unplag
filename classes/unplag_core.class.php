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
 * unplag_core.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes;

use coding_exception;
use core\event\base;

require_once(dirname(__FILE__) . '/unplag_api.class.php');
require_once(dirname(__FILE__) . '/../constants.php');

/**
 * Class unplag_core
 * @package plagiarism_unplag\classes
 */
class unplag_core {
    /** @var unplag_plagiarism_entity */
    private $unplagplagiarismentity;

    /**
     * unplag_core constructor.
     *
     * @param $cmid
     * @param $userid
     */
    public function __construct($cmid, $userid) {
        $this->cmid = $cmid;
        $this->userid = $userid;
        $this->component = 'assignsubmission_file';
    }

    /**
     * @param base $event
     *
     * @throws \Exception
     */
    public static function validate_event(base $event) {
        global $DB;

        $cmid = $event->contextinstanceid;

        $plagiarismvalues = $DB->get_records_menu(UNPLAG_CONFIG_TABLE, ['cm' => $cmid], '', 'name, value');
        if (empty($plagiarismvalues['use_unplag'])) {
            // Unplag not in use for this cm - return.
            throw new \Exception('Unplag not in use for this cm');
        }

        // Check if the module associated with this event still exists.
        if (!$DB->record_exists('course_modules', ['id' => $cmid])) {
            throw new \Exception('Module not associated with this event');
        }
    }

    /**
     * @param $checkstatusforthisids
     * @param $resp
     */
    public static function check_real_file_progress($checkstatusforthisids, &$resp) {
        $progresses = unplag_api::instance()->get_check_progress($checkstatusforthisids);
        if ($progresses->result) {
            foreach ($progresses->progress as $id => $val) {
                $resp[$id]['progress'] = $val * 100;

                self::update_file_progress($id, $resp[$id]['progress']);
            }
        }
    }

    /**
     * @param $id
     * @param $progres
     *
     * @throws UnplagException
     */
    private static function update_file_progress($id, $progres) {
        global $DB;

        $record = $DB->get_record(UNPLAG_FILES_TABLE, ['check_id' => $id]);
        if ($record->progress <= $progres) {
            $record->progress = $progres;

            if ($record->progress === 100) {
                $resp = unplag_api::instance()->get_check_data($id);
                if (!$resp->result) {
                    throw new UnplagException($resp->errors);
                }

                self::check_complete($record, $resp->check);
            } else {
                $DB->update_record(UNPLAG_FILES_TABLE, $record);
            }
        }
    }

    /**
     * @param \stdClass $record
     * @param \stdClass $check
     */
    public static function check_complete(\stdClass &$record, \stdClass $check) {
        global $DB;

        $record->statuscode = UNPLAG_STATUSCODE_PROCESSED;
        $record->similarityscore = $check->report->similarity;
        $record->reporturl = $check->report->view_url;
        $record->reportediturl = $check->report->view_edit_url;
        $record->progress = 100;

        $updated = $DB->update_record(UNPLAG_FILES_TABLE, $record);

        $emailstudents = self::get_assign_settings($record->cm, 'unplag_studentemail');
        if ($updated && !empty($emailstudents)) {
            self::send_student_email_notification($record);
        }
    }

    /**
     * @param      $cmid
     * @param null $name
     *
     * @return array
     */
    public static function get_assign_settings($cmid, $name = null) {
        global $DB;

        $condition = [
            'cm' => $cmid,
        ];

        if (isset($name)) {
            $condition['name'] = $name;
        }

        $data = $DB->get_records(UNPLAG_CONFIG_TABLE, $condition, '', 'name,value');

        return $data;
    }

    /**
     * @param $plagiarismfile
     *
     * @return null
     * @throws \moodle_exception
     * @throws coding_exception
     */
    public static function send_student_email_notification($plagiarismfile) {
        global $DB, $CFG;

        if (empty($plagiarismfile->userid)) {
            // Sanity check.
            return null;
        }

        $user = $DB->get_record('user', ['id' => $plagiarismfile->userid]);
        $site = get_site();
        $a = new \stdClass();
        $cm = get_coursemodule_from_id('', $plagiarismfile->cm);
        $a->modulename = format_string($cm->name);
        $a->modulelink = $CFG->wwwroot . '/mod/' . $cm->modname . '/view.php?id=' . $cm->id;
        $a->coursename = format_string($DB->get_field('course', 'fullname', ['id' => $cm->course]));
        $a->optoutlink = $plagiarismfile->optout;
        $emailsubject = get_string('studentemailsubject', 'plagiarism_unplag');
        $emailcontent = get_string('studentemailcontent', 'plagiarism_unplag', $a);

        email_to_user($user, $site->shortname, $emailsubject, $emailcontent);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function parse_json($data) {
        return json_decode($data);
    }

    /**
     * @param $data
     *
     * @return string
     */
    public static function json_response($data) {
        return json_encode($data);
    }

    /**
     * @param $id
     *
     * @return null
     * @throws coding_exception
     */
    public static function resubmit_file($id) {
        global $DB;

        $plagiarismfile = $DB->get_record(UNPLAG_FILES_TABLE, ['id' => $id], '*', MUST_EXIST);

        if (in_array($plagiarismfile->statuscode, [UNPLAG_STATUSCODE_PROCESSED, UNPLAG_STATUSCODE_ACCEPTED])) {
            // Sanity Check.
            return null;
        }

        $cm = get_coursemodule_from_id('', $plagiarismfile->cm);

        if ($cm->modname == 'assign') {
            $file = get_file_storage()->get_file_by_hash($plagiarismfile->identifier);
            $ucore = new unplag_core($plagiarismfile->cm, $plagiarismfile->userid);
            $plagiarismentity = $ucore->get_plagiarism_entity($file);
            $internalfile = $plagiarismentity->get_internal_file();
            if ($internalfile->check_id) {
                unplag_api::instance()->delete_check($internalfile);
            }

            $checkresp = unplag_api::instance()->run_check($internalfile);
            if ($checkresp->result === true) {
                $plagiarismentity->update_file_accepted($checkresp->check);
            } else {
                $plagiarismentity->store_file_errors($checkresp);
            }
        }
    }

    /**
     * @param $file
     *
     * @return null|unplag_plagiarism_entity
     */
    public function get_plagiarism_entity($file) {
        if (empty($file)) {
            return null;
        }
        require_once(dirname(__FILE__) . '/unplag_plagiarism_entity.php');
        $this->unplagplagiarismentity = new unplag_plagiarism_entity($this, $file);

        return $this->unplagplagiarismentity;
    }

    /**
     * This function should be used to initialise settings and check if plagiarism is enabled.
     *
     * @param null $key
     *
     * @return array|bool
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_settings($key = null) {
        static $settings;

        if (!empty($settings)) {
            return isset($settings[$key]) ? $settings[$key] : $settings;
        }

        $settings = (array)get_config('plagiarism_unplag');

        // Check if enabled.
        if (isset($settings['unplag_use']) && $settings['unplag_use']) {
            // Now check to make sure required settings are set!
            if (empty($settings['unplag_api_secret'])) {
                error("UNPLAG API Secret not set!");
            }

            return isset($settings[$key]) ? $settings[$key] : $settings;
        } else {
            return false;
        }
    }

    /**
     * @param base $event
     *
     * @return \stored_file
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function create_file_from_content(base $event) {
        global $USER;

        $filename = sprintf("online-text-content-%d-%d-%d.html", $event->contextid, $this->cmid, $USER->id);

        $filerecord = [
            'component' => UNPLAG_PLAGIN_NAME,
            'filearea'  => UNPLAG_FILES_AREA,
            'contextid' => $event->contextinstanceid,
            'itemid'    => $event->objectid,
            'filename'  => $filename,
            'filepath'  => '/',
            'userid'    => $USER->id,
            'license'   => 'allrightsreserved',
            'author'    => $USER->firstname . ' ' . $USER->lastname,
        ];

        $file = get_file_storage()->get_file(
            $event->contextid, $filerecord['component'], $filerecord['filearea'],
            $filerecord['itemid'], $filerecord['filepath'], $filerecord['filename']
        );

        if ($file) {
            return $file;
        }

        return get_file_storage()->create_file_from_string($filerecord, $event->other['content']);
    }
}

/**
 * Class UnplagException
 * @package plagiarism_unplag\classes
 */
class UnplagException extends \Exception {
    /**
     * UnplagException constructor.
     *
     * @param string $errors
     */
    public function __construct($errors) {
        $errors = array_shift($errors);

        throw new \InvalidArgumentException($errors->message);
    }
}