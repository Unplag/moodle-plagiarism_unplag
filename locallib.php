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
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);

use core\event\base;
use plagiarism_unplag\classes\event\unplag_event_assessable_submited;
use plagiarism_unplag\classes\event\unplag_event_file_submited;
use plagiarism_unplag\classes\event\unplag_event_onlinetext_submited;
use plagiarism_unplag\classes\event\unplag_event_validator;
use plagiarism_unplag\classes\event\unplag_event_workshop_switched;
use plagiarism_unplag\classes\helpers\unplag_progress;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_settings;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/constants.php');
require_once(dirname(__FILE__) . '/autoloader.php');

global $CFG;

require_once($CFG->libdir . '/filelib.php');

/**
 * Class plagiarism_unplag
 */
class plagiarism_unplag {

    /**
     * @var array
     */
    private static $supportedplagiarismmods = array(
            'assign', 'workshop', 'forum',
    );

    /**
     * @var array
     */
    private static $supportedarchivemimetypes = array(
            'application/zip'
    );

    /** @var array */
    private static $supportedfilearea = array(
            UNPLAG_WORKSHOP_FILES_AREA,
            UNPLAG_DEFAULT_FILES_AREA,
            UNPLAG_FORUM_FILES_AREA,
            'submission_files',
            'submission_attachment',
            'attachment'
    );

    /**
     * @param base $event
     */
    public static function event_handler(base $event) {

        unplag_event_validator::validate_event($event);
        if (self::is_allowed_events($event)) {
            $unplagcore = new unplag_core($event->get_context()->instanceid, $event->userid);

            switch ($event->component) {
                case 'assignsubmission_onlinetext':
                    unplag_event_onlinetext_submited::instance()->handle_event($unplagcore, $event);
                    break;
                case 'assignsubmission_file':
                    unplag_event_file_submited::instance()->handle_event($unplagcore, $event);
                    break;
                case 'mod_workshop':
                    $unplagcore->create_file_from_content($event);
                    break;
                case 'mod_forum':
                    unplag_event_onlinetext_submited::instance()->handle_event($unplagcore, $event);
                    unplag_event_file_submited::instance()->handle_event($unplagcore, $event);
                    break;
            }
        } else {
            if (self::is_assign_submitted($event)) {
                $unplagcore = new unplag_core($event->get_context()->instanceid, $event->userid);
                unplag_event_assessable_submited::instance()->handle_event($unplagcore, $event);
            } else {
                if (self::is_workshop_swiched($event)) {
                    $unplagcore = new unplag_core($event->get_context()->instanceid, $event->userid);
                    unplag_event_workshop_switched::instance()->handle_event($unplagcore, $event);
                }
            }
        }
    }

    /**
     * @param base $event
     *
     * @return bool
     */
    private static function is_allowed_events(base $event) {
        $eventdata = $event->get_data();
        return in_array($eventdata['eventname'], array(
                '\assignsubmission_file\event\submission_updated',
                '\assignsubmission_file\event\assessable_uploaded',
                '\assignsubmission_onlinetext\event\assessable_uploaded',
                '\mod_forum\event\assessable_uploaded',
                '\mod_workshop\event\assessable_uploaded'
        ));
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
     * @param base $event
     *
     * @return bool
     */
    private static function is_workshop_swiched(base $event) {
        return $event->target == 'phase' && $event->action == 'switched' && $event->component == 'mod_workshop';
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
     * @param $filearea
     *
     * @return bool
     */
    public static function is_support_filearea($filearea) {
        return in_array($filearea, self::$supportedfilearea);
    }

    /**
     * @param stored_file $file
     * @return bool
     */
    public static function is_archive(stored_file $file) {
        if ($mimetype = $file->get_mimetype()) {
            if (in_array($mimetype, self::$supportedarchivemimetypes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $obj
     *
     * @return array
     */
    public static function object_to_array($obj) {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }
        if (is_array($obj)) {
            $new = array();
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
     * @param string $filearea
     * @param null $itemid
     *
     * @return stored_file[]
     */
    public static function get_area_files($contextid, $filearea = UNPLAG_DEFAULT_FILES_AREA, $itemid = null) {

        $itemid = ($itemid !== null) ? $itemid : false;

        return get_file_storage()->get_area_files($contextid, UNPLAG_PLAGIN_NAME, $filearea, $itemid, null, false);
    }

    /**
     * @return array|bool
     */
    public static function is_plagin_enabled() {
        return unplag_settings::get_settings('use');
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
     * @param $context
     * @param $linkarray
     *
     * @return null|stored_file
     */
    public static function get_forum_topic_results($context, $linkarray) {
        $contenthash = unplag_core::content_hash($linkarray['content']);
        $file = unplag_core::get_file_by_hash($context->id, $contenthash);

        return $file;
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
            $checkstatusforids = array();
            foreach ($records as $record) {
                $progressinfo = unplag_progress::get_file_progress_info($record, $data->cid, $checkstatusforids);
                if ($progressinfo) {
                    $resp[$record->id] = $progressinfo;
                }
            }

            try {
                if (!empty($checkstatusforids)) {
                    unplag_core::check_real_file_progress($data->cid, $checkstatusforids, $resp);
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

        if (self::access_granted($token)) {
            $record = $DB->get_record(UNPLAG_FILES_TABLE, array('identifier' => $token));
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