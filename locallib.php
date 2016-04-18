<?php
// This file is part of the Checklist plugin for Moodle - http://moodle.org/
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
 * Stores all the functions for manipulating a plagiarism_unplag
 *
 * @package  plagiarism_unplag
 * @author
 */

use core\event\base;
use plagiarism_unplag\classes\event\unplag_event_assessable_submited;
use plagiarism_unplag\classes\event\unplag_event_file_submited;
use plagiarism_unplag\classes\event\unplag_event_onlinetext_submited;
use plagiarism_unplag\classes\unplag_core;

global $CFG;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/constants.php');
require_once(dirname(__FILE__) . '/classes/autoloader.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Class plagiarism_unplag
 */
class plagiarism_unplag {
    /**
     * @param base $event
     *
     * @throws \plagiarism_unplag\classes\UnplagException
     */
    public static function event_handler(base $event) {
        if (self::is_allowed_events($event)) {
            $unplagcore = new unplag_core($event->get_context()->instanceid, $event->userid);

            switch ($event->component) {
                case 'assignsubmission_onlinetext':
                    unplag_event_onlinetext_submited::instance()->handle_event($unplagcore, $event);
                    break;

                case 'assignsubmission_file':
                    unplag_event_file_submited::instance()->handle_event($unplagcore, $event);
                    break;
            }
        } else if (self::is_assign_submitted($event)) {
            $unplagcore = new unplag_core($event->get_context()->instanceid, $event->userid);
            unplag_event_assessable_submited::instance()->handle_event($unplagcore, $event);
        }
    }

    /**
     * @param base $event
     *
     * @return bool
     */
    private static function is_allowed_events(base $event) {
        return in_array($event->get_data()['eventname'], [
            '\assignsubmission_file\event\submission_updated',
            '\assignsubmission_file\event\assessable_uploaded',
            '\assignsubmission_onlinetext\event\assessable_uploaded',
        ]);
    }

    /**
     * @param base $event
     *
     * @return bool
     */
    private static function is_assign_submitted(base $event) {
        return $event->target == 'assessable' && $event->action == 'submitted';
    }

    /**
     * @param $cmid
     *
     * @return bool
     */
    public static function is_submition_draft($cmid) {
        global $CFG, $USER;

        if (!$cmid) {
            throw new InvalidArgumentException('Invalid param $cmid');
        }

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        try {
            $modulecontext = context_module::instance($cmid);
            $assign = new assign($modulecontext, false, false);
        } catch (\Exception $ex) {
            return null;
        }

        return ($assign->get_user_submission($USER->id, false)->status == 'draft');
    }

    /**
     * @param $obj
     *
     * @return array
     */
    public static function object_to_array($obj) {
        if (is_object($obj)) {
            $obj = (array)$obj;
        }
        if (is_array($obj)) {
            $new = [];
            foreach ($obj as $key => $val) {
                $new[$key] = self::object_to_array($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }

    /**
     * @param $contextid
     *
     * @return stored_file[]
     */
    public static function get_area_files($contextid) {
        return get_file_storage()->get_area_files($contextid, UNPLAG_PLAGIN_NAME, UNPLAG_FILES_AREA, false, null, false);
    }

    /**
     * @param $data
     *
     * @return string
     */
    public function track_progress($data) {
        global $DB;

        $data = unplag_core::parse_json($data);

        $resp = null;
        $records = $DB->get_records_list(UNPLAG_FILES_TABLE, 'id', $data->ids);
        if ($records) {
            $checkstatusforthisids = [];
            foreach ($records as $record) {
                if (empty($record->check_id)) {
                    continue;
                }
                if ($record->progress != 100) {
                    array_push($checkstatusforthisids, $record->check_id);
                }

                $resp[$record->check_id] = [
                    'file_id'  => $record->id,
                    'progress' => (int)$record->progress,
                ];
            }

            try {
                if (!empty($checkstatusforthisids)) {
                    unplag_core::check_real_file_progress($checkstatusforthisids, $resp);
                }
            } catch (\Exception $ex) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                $resp['error'] = $ex->getMessage();
            }
        }

        return unplag_core::json_response($resp);
    }

    /**
     * @param $token
     *
     * @throws moodle_exception
     */
    public function unplag_callback($token) {
        global $DB;
        if ($token && strlen($token) === 40) {
            $record = $DB->get_record(UNPLAG_FILES_TABLE, ['identifier' => $token]);
            if ($record) {
                $rawjson = file_get_contents('php://input');
                $check = unplag_core::parse_json($rawjson);
                unplag_core::check_complete($record, $check);
            }
        } else {
            print_error('error');
        }
    }
}