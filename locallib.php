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
 * locallib.php - Stores all the functions for manipulating a plagiarism_unplag
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\event\base;
use plagiarism_unplag\classes\event\unplag_event_assessable_submited;
use plagiarism_unplag\classes\event\unplag_event_file_submited;
use plagiarism_unplag\classes\event\unplag_event_onlinetext_submited;
use plagiarism_unplag\classes\unplag_core;

global $CFG;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/constants.php');
require_once(dirname(__FILE__) . '/autoloader.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Class plagiarism_unplag
 */
class plagiarism_unplag {
    /** @var array */
    private static $supportedplagiarismmods = [
        'assign', 'workshop', 'forum',
    ];

    /**
     * @param base $event
     */
    public static function event_handler(base $event) {

        unplag_core::validate_event($event);

        if (self::is_allowed_events($event)) {
            $unplagcore = new unplag_core($event->get_context()->instanceid, $event->userid);

            switch ($event->component) {
                case 'assignsubmission_onlinetext':
                    unplag_event_onlinetext_submited::instance()->handle_event($unplagcore, $event);
                    break;

                case 'assignsubmission_file':
                case 'mod_workshop':
                case 'mod_forum':
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
            '\mod_workshop\event\assessable_uploaded',
            '\mod_forum\event\assessable_uploaded',
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
     * @param $modname
     *
     * @return bool
     */
    public static function is_support_mod($modname) {
        return in_array($modname, self::$supportedplagiarismmods);
    }

    /**
     * @param $component
     * @param $cmid
     *
     * @return bool
     */
    public static function is_submition_draft($component, $cmid) {
        global $CFG, $USER;

        if ($component != 'mod_assign') {
            return false;
        }

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        try {
            $modulecontext = context_module::instance($cmid);
            $assign = new assign($modulecontext, false, false);
        } catch (\Exception $ex) {
            return false;
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
     * @return array|bool
     */
    public static function is_plagin_enabled() {
        return unplag_core::get_settings('use');
    }

    /**
     * @param      $message
     * @param null $param
     *
     * @return string
     * @throws coding_exception
     */
    public static function trans($message, $param = null) {
        return get_string($message, 'plagiarism_unplag', $param);
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
                    'content'  => self::gen_row_content_score($data->cid, $record),
                ];
            }

            try {
                if (!empty($checkstatusforthisids)) {
                    unplag_core::check_real_file_progress($data->cid, $checkstatusforthisids, $resp);
                }
            } catch (\Exception $ex) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                $resp['error'] = $ex->getMessage();
            }
        }

        return unplag_core::json_response($resp);
    }

    /**
     * @param $cid
     * @param $fileobj
     *
     * @return mixed
     */
    public static function gen_row_content_score($cid, $fileobj) {
        if ($fileobj->progress == 100) {
            $linkarray['cmid'] = $cid;

            return require(dirname(__FILE__) . '/view_tmpl_processed.php');
        }
    }

    /**
     * @param $token
     *
     * @throws moodle_exception
     */
    public function unplag_callback($token) {
        global $DB;

        if (self::access_granted($token)) {
            $record = $DB->get_record(UNPLAG_FILES_TABLE, ['identifier' => $token]);
            $rawjson = file_get_contents('php://input');
            $respcheck = unplag_core::parse_json($rawjson);
            if ($record && isset($respcheck->check)) {
                unplag_core::check_complete($record, $respcheck->check);
            }
        } else {
            print_error('error');
        }
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private static function access_granted($token) {
        return ($token && strlen($token) === 40 && $_SERVER['REQUEST_METHOD'] == 'POST');
    }
}