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

use assign;
use context_module;
use coding_exception;
use core\event\base;
use plagiarism_unplag;
use stored_file;
use workshop;

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
     * @param $cid
     * @param $checkstatusforthisids
     * @param $resp
     *
     * @throws UnplagException
     */
    public static function check_real_file_progress($cid, $checkstatusforthisids, &$resp) {
        $progresses = unplag_api::instance()->get_check_progress($checkstatusforthisids);
        if ($progresses->result) {
            foreach ($progresses->progress as $id => $val) {
                $val *= 100;
                $fileobj = self::update_file_progress($id, $val);
                $resp[$id]['progress'] = $val;
                $resp[$id]['content'] = plagiarism_unplag::gen_row_content_score($cid, $fileobj);
            }
        }
    }

    /**
     * @param $id
     * @param $progres
     *
     * @return mixed
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

        return $record;
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
     * @param bool $assoc
     *
     * @return \stdClass|array
     */
    public static function get_assign_settings($cmid, $name = null, $assoc = false) {
        global $DB;

        $condition = [
            'cm' => $cmid,
        ];

        if (isset($name)) {
            $condition['name'] = $name;
        }

        $data = $DB->get_records(UNPLAG_CONFIG_TABLE, $condition, '', 'name,value');
        $data = array_map(function ($item) {
            return $item->value;
        }, $data);

        return $assoc ? $data : $data[$name];
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
        $emailsubject = plagiarism_unplag::trans('studentemailsubject');
        $emailcontent = plagiarism_unplag::trans('studentemailcontent', $a);

        email_to_user($user, $site->shortname, $emailsubject, $emailcontent);
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

        if (plagiarism_unplag::is_support_mod($cm->modname)) {
            $file = get_file_storage()->get_file_by_hash($plagiarismfile->identifier);
            $ucore = new unplag_core($plagiarismfile->cm, $plagiarismfile->userid);
            $plagiarismentity = $ucore->get_plagiarism_entity($file);
            $internalfile = $plagiarismentity->get_internal_file();
            if (isset($internalfile->external_file_id)) {
                if ($internalfile->check_id) {
                    unplag_api::instance()->delete_check($internalfile);
                }

                unplag_notification::success('plagiarism_run_success');

                $checkresp = unplag_api::instance()->run_check($internalfile);
                $plagiarismentity->handle_check_response($checkresp);
            } else {
                $error = self::parse_json($internalfile->errorresponse);
                unplag_notification::error('Can\'t restart check: ' . $error[0]->message, false);
            }
        }
    }

    /**
     * @param $id
     *
     * @return null
     * @throws coding_exception
     */
    public static function check_submitted_assignment($id) {
        global $DB;

        $plagiarismfile = $DB->get_record(UNPLAG_FILES_TABLE, ['id' => $id], '*', MUST_EXIST);
        if (in_array($plagiarismfile->statuscode, [UNPLAG_STATUSCODE_PROCESSED, UNPLAG_STATUSCODE_ACCEPTED])) {
            // Sanity Check.
            return null;
        }

        $cm = get_coursemodule_from_id('', $plagiarismfile->cm);

        if (plagiarism_unplag::is_support_mod($cm->modname)) {

            $file = get_file_storage()->get_file_by_hash($plagiarismfile->identifier);
            if ($file->is_directory()) {
                return null;
            }
            $ucore = new unplag_core($plagiarismfile->cm, $plagiarismfile->userid);
            $plagiarismentity = $ucore->get_plagiarism_entity($file);

            $internalfile = $plagiarismentity->upload_file_on_unplag_server();

            if (isset($internalfile->external_file_id)) {
                if ($internalfile->check_id) {
                    unplag_api::instance()->delete_check($internalfile);
                }

                unplag_notification::success('plagiarism_run_success');

                $checkresp = unplag_api::instance()->run_check($internalfile);
                $plagiarismentity->handle_check_response($checkresp);
            } else {
                $error = self::parse_json($internalfile->errorresponse);
                unplag_notification::error('Can\'t start check: ' . $error[0]->message, false);
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

        $this->unplagplagiarismentity = new unplag_plagiarism_entity($this, $file);

        return $this->unplagplagiarismentity;
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
            return self::get_settings_item($settings, $key);
        }

        $settings = (array)get_config('plagiarism');

        // Check if enabled.
        if (isset($settings['unplag_use']) && $settings['unplag_use']) {
            // Now check to make sure required settings are set!
            if (empty($settings['unplag_api_secret'])) {
                error("UNPLAG API Secret not set!");
            }

            return self::get_settings_item($settings, $key);
        } else {
            return false;
        }
    }

    /**
     * @param      $settings
     * @param null $key
     *
     * @return null
     */
    private static function get_settings_item($settings, $key = null) {
        if (is_null($key)) {
            return $settings;
        }

        $key = 'unplag_' . $key;

        return isset($settings[$key]) ? $settings[$key] : null;
    }

    /**
     * @param $contextid
     * @param $contenthash
     *
     * @return null|stored_file
     */
    public static function get_file_by_hash($contextid, $contenthash) {
        global $DB;

        $filerecord = $DB->get_record('files', [
            'contextid'   => $contextid,
            'component'   => UNPLAG_PLAGIN_NAME,
            'contenthash' => $contenthash,
        ]);

        if (!$filerecord) {
            return null;
        }

        return get_file_storage()->get_file_instance($filerecord);
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

        if (empty($event->other['content'])) {
            return false;
        }

        $filerecord = [
            'component' => UNPLAG_PLAGIN_NAME,
            'filearea'  => $event->objecttable,
            'contextid' => $event->contextid,
            'itemid'    => $event->objectid,
            'filename'  => sprintf("%s-content-%d-%d-%d.html",
                str_replace('_', '-', $event->objecttable), $event->contextid, $this->cmid, $event->objectid
            ),
            'filepath'  => '/',
            'userid'    => $USER->id,
            'license'   => 'allrightsreserved',
            'author'    => $USER->firstname . ' ' . $USER->lastname,
        ];

        /** @var \stored_file $storedfile */
        $storedfile = get_file_storage()->get_file(
            $filerecord['contextid'], $filerecord['component'], $filerecord['filearea'],
            $filerecord['itemid'], $filerecord['filepath'], $filerecord['filename']
        );

        if ($storedfile && $storedfile->get_contenthash() != self::content_hash($event->other['content'])) {
            $this->delete_old_file_from_content($storedfile);
        }

        return get_file_storage()->create_file_from_string($filerecord, $event->other['content']);
    }

    /**
     * @param $content
     *
     * @return string
     */
    public static function content_hash($content) {
        return sha1($content);
    }

    /**
     * @param \stored_file $storedfile
     */
    private function delete_old_file_from_content(\stored_file $storedfile) {
        global $DB;

        $DB->delete_records(UNPLAG_FILES_TABLE, [
            'cm'         => $this->cmid,
            'userid'     => $storedfile->get_userid(),
            'identifier' => $storedfile->get_pathnamehash(),
        ]);

        $storedfile->delete();
    }

    /**
     * @param      $cmid
     * @param null $user_id
     *
     * @return bool
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
     * @param      $cm
     * @param null $userid
     *
     * @return bool
     */
    public static function get_user_workshop_submission_by_cm($cm, $userid = null) {
        global $USER, $DB;

        try {
            $workshoprecord = $DB->get_record('workshop', array('id' => $cm->instance), '*', MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
            $workshop = new workshop($workshoprecord, $cm, $course);
        } catch (\Exception $ex) {
            return false;
        }

        return ($workshop->get_submission_by_author(($userid !== null) ? $userid : $USER->id, false));
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