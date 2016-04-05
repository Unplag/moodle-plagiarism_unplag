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
        global $DB, $CFG;

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

        if (in_array($event->component, ['assignsubmission_file', 'assignsubmission_onlinetext', 'mod_assign'])) {
            /*switch ($event->component) {
                case 'mod_assign':
                    require_once("$CFG->dirroot/mod/assign/locallib.php");
                    require_once("$CFG->dirroot/mod/assign/submission/file/locallib.php");
                    break;

                default:
                    require_once("$CFG->dirroot/mod/assignment/lib.php");
                    break;
            }*/
            $fs = get_file_storage();
            $modulecontext = context_module::instance($event->contextinstanceid);
            $files = $fs->get_area_files($modulecontext->id, 'assignsubmission_file', 'submission_files');
            //var_dump($files);
            //die;
            if ($files) {
                $unplag_core = new unplag_core($event->contextinstanceid, $event->userid);
                foreach ($files as $file) {
                    if ($file->is_directory()) {
                        continue;
                    }
                    $sendresult = $unplag_core->handle_uploaded_file($file);
                }
            }
            die;
            var_dump($modulecontext->id, $event->contextinstanceid);
            die;
            /*
             $assignmentbase = new assign($modulecontext, null, null);
             $submission = $assignmentbase->get_submission($event->userid);*/

            // $fs = get_file_storage();
            //$files = $fs->get_area_files($modulecontext->id, 'assignsubmission_file', null, $event->objectid, "id", false);
            /* var_dump($files);
             die;*/
            //mail('v.titov@p1k.co.uk', 'moodle events', print_r($event, true));
        }
        //die;
    }
}