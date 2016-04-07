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

use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;

define('UNPLAG_MOD_NAME', 'plagiarism_unplag');
define('UNPLAG_PROJECT_PATH', dirname(__FILE__) . '/');

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(UNPLAG_PROJECT_PATH . 'classes/unplag_core.class.php');
//require_once($CFG->dirroot.'/mod/lti/OAuth.php');

/**
 * Class plagiarism_unplag
 */
class plagiarism_unplag {
    /**
     * @param \core\event\base $event
     */
    public static function event_handler(\core\event\base $event) {
        global $DB;
        $resp = unplag_api::instance()->get_check_progress([7243, 7244]);
        var_dump($resp);die;
        //mail('v.titov@p1k.co.uk', 'moodle events', print_r($event, true));
        // var_dump($event->target, $event->action, $event->eventname, $event->component, $event->get_data());
//die;
        /*try {
            unplag_core::validate_event($event);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }*/

        /*if ($event->target == 'course_module' && $event->action == 'created') {
        }*/

        if (in_array($event->component, ['assignsubmission_file', 'assignsubmission_onlinetext'])) {
            $unplag_core = new unplag_core($event->get_context()->instanceid, $event->userid);

            if (isset($event->other['content'])) {
                $submission = $DB->get_record('assignsubmission_onlinetext', ['submission' => $event->objectid]);

                if (self::is_content_changed($submission->onlinetext, $event->other['content'])) {
                    $file = $unplag_core->create_temp_file($event);
                }
                mtrace('upload text');
                $sendresult = $unplag_core->handle_uploaded_file($file);
                $file->delete();
            }

            $fs = get_file_storage();
            $files = $fs->get_area_files($event->get_context()->id, 'assignsubmission_file', 'submission_files');
            if ($files) {
                foreach ($files as $file) {
                    if ($file->is_directory()) {
                        continue;
                    }
                    mtrace('upload file');
                    $sendresult = $unplag_core->handle_uploaded_file($file);
                }
            }
        }
    }

    /**
     * @param $onlinetext
     * @param $content
     *
     * @return bool
     */
    private static function is_content_changed($onlinetext, $content) {
        return base64_encode($onlinetext) !== base64_encode($content);
    }

    /**
     * @param $data
     *
     * @return string
     */
    public function track_progress($data) {
        global $DB;

        self::parse_json($data);

        $resp = unplag_api::instance()->get_check_progress([7243, 7244]);
        var_dump($resp);die;

        $resp = null;
        $records = $DB->get_records_list(unplag_core::UNPLAG_FILES_TABLE, 'id', $data->ids);
        if ($records) {
            foreach ($records as $record) {
                $resp[] = [
                    'file_id'  => $record->id,
                    'progress' => (int)$record->progress + 100,
                ];
            }
        }

        return self::json_response($resp);
    }

    /**
     * @param $data
     */
    private static function parse_json(&$data) {
        $data = json_decode($data);
    }

    /**
     * @param $data
     *
     * @return string
     */
    private static function json_response($data) {
        return json_encode($data);
    }
}