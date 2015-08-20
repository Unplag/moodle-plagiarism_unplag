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
 * lib.php - Contains Plagiarism plugin specific functions called by Modules.
 *
 * @since 2.0
 * @package    plagiarism_unplag
 * @subpackage plagiarism
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2011 Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

// Get global class.
global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');
require_once($CFG->dirroot . '/lib/filelib.php');

// There is a new UNPLAG API - The Integration Service - we only currently use this to verify the receiver address.
// If we convert the existing calls to send file/get score we should move this to a config setting.
define('UNPLAG_INTEGRATION_SERVICE', 'https://secure.unplag.com/api');

define('UNPLAG_MAX_SUBMISSION_ATTEMPTS', 6); // Maximum number of times to try and send a submission to UNPLAG.
define('UNPLAG_MAX_SUBMISSION_DELAY', 60); // Maximum time to wait between submissions (defined in minutes).
define('UNPLAG_SUBMISSION_DELAY', 15); // Initial delay, doubled each time a check is made until the max_submission_delay is met.
define('UNPLAG_MAX_STATUS_ATTEMPTS', 10); // Maximum number of times to try and obtain the status of a submission.
define('UNPLAG_MAX_STATUS_DELAY', 1440); // Maximum time to wait between checks (defined in minutes).
define('UNPLAG_STATUS_DELAY', 30); // Initial delay, doubled each time a check is made until the max_status_delay is met.
define('UNPLAG_STATUSCODE_PROCESSED', '200');
define('UNPLAG_STATUSCODE_ACCEPTED', '202');
define('UNPLAG_STATUSCODE_BAD_REQUEST', '400');
define('UNPLAG_STATUSCODE_NOT_FOUND', '404');
define('UNPLAG_STATUSCODE_GONE', '410'); // Receiver is inactive or deleted.
define('UNPLAG_STATUSCODE_UNSUPPORTED', '415');
define('UNPLAG_STATUSCODE_TOO_LARGE', '413');
define('UNPLAG_STATUSCODE_NORECEIVER', '444');
define('UNPLAG_STATUSCODE_INVALID_RESPONSE', '613'); // Invalid response received from UNPLAG.

// Url to external xml that states UNPLAGS allowed file type list.
define('UNPLAG_DOMAIN', 'http://plag.karmastat.in/');

define('UNPLAG_FILETYPE_URL_UPDATE', '168'); // How often to check for updated file types (defined in hours).

define('PLAGIARISM_UNPLAG_SHOW_NEVER', 0);
define('PLAGIARISM_UNPLAG_SHOW_ALWAYS', 1);
define('PLAGIARISM_UNPLAG_SHOW_CLOSED', 2);

define('PLAGIARISM_UNPLAG_DRAFTSUBMIT_IMMEDIATE', 0);
define('PLAGIARISM_UNPLAG_DRAFTSUBMIT_FINAL', 1);


class plagiarism_plugin_unplag extends plagiarism_plugin {
    /**
     * This function should be used to initialise settings and check if plagiarism is enabled.
     *
     * @return mixed - false if not enabled, or returns an array of relevant settings.
     */
    static public function get_settings() {
        static $plagiarismsettings;
        if (!empty($plagiarismsettings) || $plagiarismsettings === false) {
            return $plagiarismsettings;
        }
        $plagiarismsettings = (array)get_config('plagiarism');
        // Check if enabled.
        if (isset($plagiarismsettings['unplag_use']) && $plagiarismsettings['unplag_use']) {
            // Now check to make sure required settings are set!
            if (empty($plagiarismsettings['unplag_api_secret'])) {
                error("UNPLAG API Secret not set!");
            }
            return $plagiarismsettings;
        } else {
            return false;
        }
    }
    /**
     * Function which returns an array of all the module instance settings.
     *
     * @return array
     *
     */
    public function config_options() {
        return array('use_unplag', 'unplag_show_student_score', 'unplag_show_student_report',
                     'unplag_draft_submit', 'unplag_receiver', 'unplag_studentemail');
    }
    /**
     * Hook to allow plagiarism specific information to be displayed beside a submission.
     * @param array  $linkarraycontains all relevant information for the plugin to generate a link.
     * @return string
     *
     */
    public function get_links($linkarray) {
        //online submission view is unavailable due https://tracker.moodle.org/browse/MDL-40460
        global $COURSE, $OUTPUT, $CFG;
        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        if (!empty($linkarray['content'])) {
            $filename = "content-" . $COURSE->id . "-" . $cmid . "-". $userid . ".htm";
            $filepath = $CFG->tempdir."/unplag/" . $filename;
            $file = new stdclass();
            $file->type = "tempunplag";
            $file->filename = $filename;
            $file->timestamp = time();
            $file->identifier = sha1($linkarray['content']);
            $file->filepath = $filepath;
        } else if (!empty($linkarray['file'])) {
            $file = new stdclass();
            $file->filename = $linkarray['file']->get_filename();
            $file->timestamp = time();
            $file->identifier = $linkarray['file']->get_contenthash();
            $file->filepath = $linkarray['file']->get_filepath();
        }
        $results = $this->get_file_results($cmid, $userid, $file);
        if (empty($results)) {
            // Info about this file is not available to this user.
            return '';
        }
        $modulecontext = context_module::instance($cmid);
        //print_r($linkarray);die;
        $output = '';


        $errors=false;
        if($results['statuscode'] == UNPLAG_STATUSCODE_INVALID_RESPONSE){
            $errors = json_decode($results['error'], true);
            
        }
        
        if ($results['statuscode'] == UNPLAG_STATUSCODE_PROCESSED) {
            // Normal situation - UNPLAG has successfully analyzed the file.
            $rank = unplag_get_css_rank($results['score']);
            $output .= '<span class="plagiarismreport">';
            if (!empty($results['optoutlink'])) {
                // User is allowed to view the report.
                // Score is contained in report, so they can see the score too.
                $output .= ' <a href="' . UNPLAG_DOMAIN.$results['optoutlink'] . '" title="'.get_string('plagiarism', 'plagiarism_unplag').'">';
                $output .= '<img  width="32" height="32" src="'.$OUTPUT->pix_url('unplag', 'plagiarism_unplag').'"> ';
                $output .= '<span class="'.$rank.'">'.$results['score'].'%</span>';
                $output .= '</a>';
            } else if ($results['score'] !== '') {
                // User is allowed to view only the score.
                $output .= get_string('similarity', 'plagiarism_unplag') . ':';
                $output .= '<span class="' . $rank . '">' . $results['score'] . '%</span>';
            }
            if (!empty($results['optoutlink'])) {
                // Display opt-out link.
                $output .= '&nbsp;<span class"plagiarismoptout">' .
                        '<a title="'.get_string('report', 'plagiarism_unplag').'" href="' . UNPLAG_DOMAIN.$results['optoutlink'] . '" >' .
                        '<img src="'.$OUTPUT->pix_url('dwnld', 'plagiarism_unplag').'">'.
                        '</a></span>';
            }
            if (!empty($results['renamed'])) {
                $output .= $results['renamed'];
            }
            $output .= '</span>';
        } elseif ($results['statuscode'] == UNPLAG_STATUSCODE_ACCEPTED) {
            $output .= '<span class="plagiarismreport">'.
                       '<img class="un_progress" src="'.$OUTPUT->pix_url('progress', 'plagiarism_unplag') .
                        '" alt="'.get_string('processing', 'plagiarism_unplag').'" '.
                        '" title="'.get_string('processing', 'plagiarism_unplag').'" /> '.
                        get_string('progress', 'plagiarism_unplag').' : '.$results['progress'].'%</span>';
        } else if ($results['statuscode'] == UNPLAG_STATUSCODE_INVALID_RESPONSE && $errors && array_key_exists('format', $errors)) {
            $output .= '<span class="plagiarismreport">'.
                       '<img  width="40" height="40" src="'.$OUTPUT->pix_url('warn', 'plagiarism_unplag') .
                        '" alt="'.get_string('unsupportedfiletype', 'plagiarism_unplag').'" '.
                        '" title="'.get_string('unsupportedfiletype', 'plagiarism_unplag').'" />'.
                        '</span>';
        }  else {
            $title = get_string('unknownwarning', 'plagiarism_unplag');
            $reset = '';
            if (has_capability('plagiarism/unplag:resetfile', $modulecontext) &&
                !empty($results['error'])) { // This is a teacher viewing the responses.
                // Strip out some possible known text to tidy it up.
                $erroresponse = format_text(implode(',',  array_values($errors)), FORMAT_PLAIN);
                $erroresponse = str_replace('{&quot;LocalisedMessage&quot;:&quot;', '', $erroresponse);
                $erroresponse = str_replace('&quot;,&quot;Message&quot;:null}', '', $erroresponse);
                $title .= ': ' . $erroresponse;
                $url = new moodle_url('/plagiarism/unplag/reset.php', array('cmid' => $cmid, 'pf' => $results['pid'],
                                                                            'sesskey' => sesskey()));
                $reset = " <a href='$url'>".get_string('reset')."</a>";
            }
            $output .= '<span class="plagiarismreport">'.
                       '<img width="40" height="40" src="'.$OUTPUT->pix_url('warn', 'plagiarism_unplag') .
                        '" alt="'.get_string('unknownwarning', 'plagiarism_unplag').'" '.
                        '" title="'.$title.'" />'.$reset.'</span>';
        }
        return $output;
    }

    public function get_file_results($cmid, $userid, $file) {
        global $DB, $USER, $CFG;
        $plagiarismsettings = $this->get_settings();
        if (empty($plagiarismsettings)) {
            // Unplag is not enabled.
            return false;
        }
        $plagiarismvalues = unplag_cm_use($cmid);
        if (empty($plagiarismvalues)) {
            // Unplag not enabled for this cm.
            return false;
        }

        // Collect detail about the specified coursemodule.
        $filehash = $file->identifier;
        $modulesql = 'SELECT m.id, m.name, cm.instance'.
                ' FROM {course_modules} cm' .
                ' INNER JOIN {modules} m on cm.module = m.id ' .
                'WHERE cm.id = ?';
        $moduledetail = $DB->get_record_sql($modulesql, array($cmid));
        if (!empty($moduledetail)) {
            $sql = "SELECT * FROM " . $CFG->prefix . $moduledetail->name . " WHERE id= ?";
            $module = $DB->get_record_sql($sql, array($moduledetail->instance));
        }
        if (empty($module)) {
            // No such cmid.
            return false;
        }

        $modulecontext = context_module::instance($cmid);
        // If the user has permission to see result of all items in this course module.
        $viewscore = $viewreport = has_capability('plagiarism/unplag:viewreport', $modulecontext);

        // Determine if the activity is closed.
        // If report is closed, this can make the report available to more users.
        $assignclosed = false;
        $time = time();
        if (!empty($module->preventlate) && !empty($module->timedue)) {
            $assignclosed = ($module->timeavailable <= $time && $time <= $module->timedue);
        } else if (!empty($module->timeavailable)) {
            $assignclosed = ($module->timeavailable <= $time);
        }

        // Under certain circumstances, users are allowed to see plagiarism info
        // even if they don't have view report capability.
        if ($USER->id == $userid) {
            $selfreport = true;
            if (isset($plagiarismvalues['unplag_show_student_report']) &&
                    ($plagiarismvalues['unplag_show_student_report'] == PLAGIARISM_UNPLAG_SHOW_ALWAYS ||
                     $plagiarismvalues['unplag_show_student_report'] == PLAGIARISM_UNPLAG_SHOW_CLOSED && $assignclosed)) {
                $viewreport = true;
            }
            if (isset($plagiarismvalues['unplag_show_student_score']) &&
                    ($plagiarismvalues['unplag_show_student_score'] == PLAGIARISM_UNPLAG_SHOW_ALWAYS) ||
                    ($plagiarismvalues['unplag_show_student_score'] == PLAGIARISM_UNPLAG_SHOW_CLOSED && $assignclosed)) {
                $viewscore = true;
            }
        } else {
            $selfreport = false;
        }
        // End of rights checking.

        if (!$viewscore && !$viewreport && !$selfreport) {
            // User is not permitted to see any details.
            return false;
        }
        $plagiarismfile = $DB->get_record_sql(
                    "SELECT * FROM {plagiarism_unplag_files}
                    WHERE cm = ? AND userid = ? AND " .
                    "identifier = ?",
                    array($cmid, $userid, $filehash));
        if (empty($plagiarismfile)) {
            // No record of that submitted file.
            return false;
        }

        // Returns after this point will include a result set describing information about
        // interactions with unplag servers.
        $results = array('statuscode' => '', 'error' => '', 'reporturl' => '',
                'score' => '', 'pid' => '', 'optoutlink' => '', 'renamed' => '',
                'analyzed' => 0,
                );
        if ($plagiarismfile->statuscode == UNPLAG_STATUSCODE_ACCEPTED) {
            $results['statuscode'] = UNPLAG_STATUSCODE_ACCEPTED;
            $results['progress'] = $plagiarismfile->progress;
            return $results;
        }

        // Now check for differing filename and display info related to it.
        $previouslysubmitted = '';
        if ($file->filename !== $plagiarismfile->filename) {
            $previouslysubmitted = '('.get_string('previouslysubmitted', 'plagiarism_unplag').': '.$plagiarismfile->filename.')';
        }

        $results['statuscode'] = $plagiarismfile->statuscode;
        $results['pid'] = $plagiarismfile->id;
        $results['error'] = $plagiarismfile->errorresponse;
        if ($plagiarismfile->statuscode == UNPLAG_STATUSCODE_PROCESSED) {
            $results['analyzed'] = 1;
            // File has been successfully analyzed - return all appropriate details.
            if ($viewscore || $viewreport) {
                // If user can see the report, they can see the score on the report
                // so make it directly available.
                $results['score'] = $plagiarismfile->similarityscore;
            }
            if ($viewreport) {
                $results['reporturl'] = $plagiarismfile->reporturl;
            }
            if (!empty($plagiarismfile->optout) && $selfreport) {
                $results['optoutlink'] = $plagiarismfile->optout;
            }
            $results['renamed'] = $previouslysubmitted;
        }
        return $results;
    }
    /* Hook to save plagiarism specific settings on a module settings page.
     * @param object $data - data from an mform submission.
    */
    public function save_form_elements($data) {
        global $DB;
        if (!$this->get_settings()) {
            return;
        }
        if (isset($data->use_unplag)) {
            // Array of possible plagiarism config options.
            $plagiarismelements = $this->config_options();
            // First get existing values.
            $existingelements = $DB->get_records_menu('plagiarism_unplag_config', array('cm' => $data->coursemodule),
                                                      '', 'name, id');
            foreach ($plagiarismelements as $element) {
                $newelement = new stdClass();
                $newelement->cm = $data->coursemodule;
                $newelement->name = $element;
                $newelement->value = (isset($data->$element) ? $data->$element : 0);
                if (isset($existingelements[$element])) {
                    $newelement->id = $existingelements[$element];
                    $DB->update_record('plagiarism_unplag_config', $newelement);
                } else {
                    $DB->insert_record('plagiarism_unplag_config', $newelement);
                }

            }
            if (!empty($data->unplag_receiver)) {
                set_user_preference('unplag_receiver', trim($data->unplag_receiver));
            }
        }
    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     * @param object $mform  - Moodle form
     * @param object $context - current context
     */
    public function get_form_elements_module($mform, $context, $modulename = "") {
        global $DB, $PAGE, $CFG;
        $plagiarismsettings = $this->get_settings();
        if (!$plagiarismsettings) {
            return;
        }
        $cmid = optional_param('update', 0, PARAM_INT); // Get cm as $this->_cm is not available here.
        if (!empty($modulename)) {
            $modname = 'unplag_enable_' . $modulename;
            if (empty($plagiarismsettings[$modname])) {
                return;             // Return if unplag is not enabled for the module.
            }
        }
        if (!empty($cmid)) {
            $plagiarismvalues = $DB->get_records_menu('plagiarism_unplag_config', array('cm' => $cmid), '', 'name, value');
        }
        // Get Defaults - cmid(0) is the default list.
        $plagiarismdefaults = $DB->get_records_menu('plagiarism_unplag_config', array('cm' => 0), '', 'name, value');
        $plagiarismelements = $this->config_options();
        if (has_capability('plagiarism/unplag:enable', $context)) {
            unplag_get_form_elements($mform);
            if ($mform->elementExists('unplag_draft_submit')) {
                if ($mform->elementExists('var4')) {
                    $mform->disabledIf('unplag_draft_submit', 'var4', 'eq', 0);
                } else if ($mform->elementExists('submissiondrafts')) {
                    $mform->disabledIf('unplag_draft_submit', 'submissiondrafts', 'eq', 0);
                }
            }
            // Disable all plagiarism elements if use_plagiarism eg 0.
            foreach ($plagiarismelements as $element) {
                if ($element <> 'use_unplag') { // Ignore this var.
                    $mform->disabledIf($element, 'use_unplag', 'eq', 0);
                }
            }
            // Check if files have been submitted and we need to disable the receiver address.
            if ($DB->record_exists('plagiarism_unplag_files', array('cm' => $cmid, 'statuscode' => 'pending'))) {
                $mform->disabledIf('unplag_receiver', 'use_unplag');
            }
        } else { // Add plagiarism settings as hidden vars.
            foreach ($plagiarismelements as $element) {
                $mform->addElement('hidden', $element);
                $mform->setType('use_unplag', PARAM_INT);
                $mform->setType('unplag_show_student_score', PARAM_INT);
                $mform->setType('unplag_show_student_report', PARAM_INT);
                $mform->setType('unplag_draft_submit', PARAM_INT);
                $mform->setType('unplag_receiver', PARAM_TEXT);
                $mform->setType('unplag_studentemail', PARAM_INT);
            }
        }
        // Now set defaults.
        foreach ($plagiarismelements as $element) {
            if (isset($plagiarismvalues[$element])) {
                $mform->setDefault($element, $plagiarismvalues[$element]);
            } else if ($element == 'unplag_receiver') {
                $def = get_user_preferences($element);
                if (!empty($def)) {
                    $mform->setDefault($element, $def);
                } else if (isset($plagiarismdefaults[$element])) {
                    $mform->setDefault($element, $plagiarismdefaults[$element]);
                }
            } else if (isset($plagiarismdefaults[$element])) {
                $mform->setDefault($element, $plagiarismdefaults[$element]);
            }
        }
        

        // Now add JS to validate receiver indicator using Ajax.
        if (has_capability('plagiarism/unplag:enable', $context)) {
            $jsmodule = array(
                'name' => 'plagiarism_unplag',
                'fullpath' => '/plagiarism/unplag/checkreceiver.js',
                'requires' => array('json'),
            );
            $PAGE->requires->js_init_call('M.plagiarism_unplag.init', array($context->instanceid), true, $jsmodule);
        }
    }

    /**
     * Hook to allow a disclosure to be printed notifying users what will happen with their submission.
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT;

        $outputhtml = '';

        $unplaguse = unplag_cm_use($cmid);
        $plagiarismsettings = $this->get_settings();
        if (!empty($plagiarismsettings['unplag_student_disclosure']) &&
            !empty($unplaguse)) {
                $outputhtml .= $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
                $formatoptions = new stdClass;
                $formatoptions->noclean = true;
                $outputhtml .= format_text($plagiarismsettings['unplag_student_disclosure'], FORMAT_MOODLE, $formatoptions);
                $outputhtml .= $OUTPUT->box_end();
        }
        return $outputhtml;
    }

    /**
     * Hook to allow status of submitted files to be updated - called on grading/report pages.
     *
     * @param object $course - full Course object
     * @param object $cm - full cm object
     * @return string
     */
    public function update_status($course, $cm) {
        // Called at top of submissions/grading pages - allows printing of admin style links or updating status.
        return '';
    }

    /**
     * Called by admin/cron.php.
     *
     */
    public function cron() {
        global $CFG;
        log_message(3, 'Cron run');
        // Do any scheduled task stuff.
        //unplag_update_allowed_filetypes();
        // Weird hack to include filelib correctly before allowing use in event_handler.
        require_once($CFG->libdir.'/filelib.php');
        
        if ($plagiarismsettings = $this->get_settings()) {
            unplag_get_scores($plagiarismsettings);
        }
    }
    /**
     * Generic handler function for all events - triggers sending of files.
     * @return boolean
     */
    public function event_handler($eventdata) {
        global $DB, $CFG;

        $supportedevents = unplag_supported_events();
        if (!in_array($eventdata->eventtype, $supportedevents)) {
            return true; // Don't need to handle this event.
        }

        $plagiarismsettings = $this->get_settings();
        if (!$plagiarismsettings) {
            return true;
        }
        $cmid = (!empty($eventdata->cm->id)) ? $eventdata->cm->id : $eventdata->cmid;
        $plagiarismvalues = $DB->get_records_menu('plagiarism_unplag_config', array('cm' => $cmid), '', 'name, value');
        if (empty($plagiarismvalues['use_unplag'])) {
            // Unplag not in use for this cm - return.
            return true;
        }

        // Check if the module associated with this event still exists.
        if (!$DB->record_exists('course_modules', array('id' => $eventdata->cmid))) {
            return true;
        }

        if ($eventdata->eventtype == 'files_done' ||
            $eventdata->eventtype == 'content_done' ||
            ($eventdata->eventtype == 'assessable_submitted' && $eventdata->params['submission_editable'] == false)) {
            // Assignment-specific functionality:
            // This is a 'finalize' event. No files from this event itself,
            // but need to check if files from previous events need to be submitted for processing.
            mtrace("finalise");
            $result = true;
            if (isset($plagiarismvalues['unplag_draft_submit']) &&
                $plagiarismvalues['unplag_draft_submit'] == PLAGIARISM_UNPLAG_DRAFTSUBMIT_FINAL) {
                // Any files attached to previous events were not submitted.
                // These files are now finalized, and should be submitted for processing.
                if ($eventdata->modulename == 'assignment') {
                    // Hack to include filelib so that file_storage class is available.
                    require_once("$CFG->dirroot/mod/assignment/lib.php");
                    // We need to get a list of files attached to this assignment and put them in an array, so that
                    // we can submit each of them for processing.
                    $assignmentbase = new assignment_base($cmid);
                    $submission = $assignmentbase->get_submission($eventdata->userid);
                    $modulecontext = context_module::instance($eventdata->cmid);
                    $fs = get_file_storage();
                    if ($files = $fs->get_area_files($modulecontext->id, 'mod_assignment', 'submission', $submission->id,
                                                     "timemodified", false)) {
                        foreach ($files as $file) {
                            $sendresult = unplag_send_file($cmid, $eventdata->userid, $file, $plagiarismsettings);
                            $result = $result && $sendresult;
                        }
                    }
                } else if ($eventdata->modulename == 'assign') {
                    require_once("$CFG->dirroot/mod/assign/locallib.php");
                    require_once("$CFG->dirroot/mod/assign/submission/file/locallib.php");

                    $modulecontext = context_module::instance($eventdata->cmid);
                    $fs = get_file_storage();
                    if ($files = $fs->get_area_files($modulecontext->id, 'assignsubmission_file',
                                                     ASSIGNSUBMISSION_FILE_FILEAREA, $eventdata->itemid, "id", false)) {
                        foreach ($files as $file) {
                            $sendresult = unplag_send_file($cmid, $eventdata->userid, $file, $plagiarismsettings);
                            $result = $result && $sendresult;
                        }
                    }
                    $submission = $DB->get_record('assignsubmission_onlinetext', array('submission' => $eventdata->itemid));
                    if (!empty($submission)) {
                        $eventdata->content = trim(format_text($submission->onlinetext, $submission->onlineformat,
                                                               array('context' => $modulecontext)));
                        $file = unplag_create_temp_file($cmid, $eventdata);
                        $sendresult = unplag_send_file($cmid, $eventdata->userid, $file, $plagiarismsettings);
                        $result = $result && $sendresult;
                        unlink($file->filepath); // Delete temp file.
                    }
                }
            }
            return $result;
        }

        if (isset($plagiarismvalues['unplag_draft_submit']) &&
            $plagiarismvalues['unplag_draft_submit'] == PLAGIARISM_UNPLAG_DRAFTSUBMIT_FINAL) {
            // Assignment-specific functionality:
            // Files should only be sent for checking once "finalized".
            return true;
        }

        // Text is attached.
        $result = true;
        if (!empty($eventdata->content)) {
            $file = unplag_create_temp_file($cmid, $eventdata);
            $sendresult = unplag_send_file($cmid, $eventdata->userid, $file, $plagiarismsettings);
            $result = $result && $sendresult;
            unlink($file->filepath); // Delete temp file.
        }

        // Normal situation: 1 or more assessable files attached to event, ready to be checked.
        if (!empty($eventdata->pathnamehashes)) {
            foreach ($eventdata->pathnamehashes as $hash) {
                $fs = get_file_storage();
                $efile = $fs->get_file_by_hash($hash);

                if (empty($efile)) {
                    mtrace("nofilefound!");
                    continue;
                } else if ($efile->get_filename() === '.') {
                    // This 'file' is actually a directory - nothing to submit.
                    continue;
                }
                // Check if assign group submission is being used.
                if ($eventdata->modulename == 'assign') {
                    require_once("$CFG->dirroot/mod/assign/locallib.php");
                    $modulecontext = context_module::instance($eventdata->cmid);
                    $assign = new assign($modulecontext, false, false);
                    if (!empty($assign->get_instance()->teamsubmission)) {
                        $mygroups = groups_get_user_groups($assign->get_course()->id, $eventdata->userid);
                        if (count($mygroups) == 1) {
                            $groupid = reset($mygroups)[0];
                            // Only users with single groups are supported - otherwise just use the normal userid on this record.
                            // Get all users from this group.
                            $userids = array();
                            $users = groups_get_members($groupid, 'u.id');
                            foreach ($users as $u) {
                                $userids[] = $u->id;
                            }
                            // Find the earliest plagiarism record for this cm with any of these users.
                            $sql ='cm = ? AND userid IN ('.implode(',', $userids).')';
                            $previousfiles = $DB->get_records_select('plagiarism_unplag_files', $sql, array($eventdata->cmid), 'id');
                            $sanitycheckusers = 10; // Search through this number of users to find a valid previous submission.
                            $i = 0;
                            foreach ($previousfiles as $pf) {
                                if ($pf->userid == $eventdata->userid) {
                                    break; // The submission comes from this user so break.
                                }
                                // Sanity Check to make sure the user isn't in multiple groups.
                                $pfgroups = groups_get_user_groups($assign->get_course()->id, $pf->userid);
                                if (count($pfgroups) == 1) {
                                    // This user made the first valid submission so use their id when sending the file.
                                    $eventdata->userid = $pf->userid;
                                    break;
                                }
                                if ($i >= $sanitycheckusers) {
                                    // don't cause a massive loop here and break at a sensible limit.
                                    break;
                                }
                                $i++;
                            }
                        }
                    }
                }

                $sendresult = unplag_send_file($cmid, $eventdata->userid, $efile, $plagiarismsettings);
                $result = $result && $sendresult;
            }
        }
        return $result;
    }

    public function unplag_send_student_email($plagiarismfile) {
        global $DB, $CFG;
        if (empty($plagiarismfile->userid)) { // Sanity check.
            return false;
        }
        $user = $DB->get_record('user', array('id' => $plagiarismfile->userid));
        $site = get_site();
        $a = new stdClass();
        $cm = get_coursemodule_from_id('', $plagiarismfile->cm);
        $a->modulename = format_string($cm->name);
        $a->modulelink = $CFG->wwwroot.'/mod/'.$cm->modname.'/view.php?id='.$cm->id;
        $a->coursename = format_string($DB->get_field('course', 'fullname', array('id' => $cm->course)));
        $a->optoutlink = $plagiarismfile->optout;
        $emailsubject = get_string('studentemailsubject', 'plagiarism_unplag');
        $emailcontent = get_string('studentemailcontent', 'plagiarism_unplag', $a);
        email_to_user($user, $site->shortname, $emailsubject, $emailcontent);
    }


}

function unplag_create_temp_file($cmid, $eventdata) {
    global $CFG;
    if (!check_dir_exists($CFG->tempdir."/unplag", true, true)) {
        mkdir($CFG->tempdir."/unplag", 0700);
    }
    $filename = "content-" . $eventdata->courseid . "-" . $cmid . "-" . $eventdata->userid . ".htm";
    $filepath = $CFG->tempdir."/unplag/" . $filename;
    $fd = fopen($filepath, 'wb');   // Create if not exist, write binary.

    // Write html and body tags as it seems that Unplag doesn't works well without them.
    $content = '<html>' .
               '<head>' .
               '<meta charset="UTF-8">' .
               '</head>' .
               '<body>' .
               $eventdata->content .
               '</body></html>';

    fwrite($fd, $content);
    fclose($fd);
    $file = new stdclass();
    $file->type = "tempunplag";
    $file->filename = $filename;
    $file->timestamp = time();
    $file->identifier = sha1($eventdata->content);
    $file->filepath = $filepath;
    return $file;
}

function unplag_event_file_uploaded($eventdata) {
    $eventdata->eventtype = 'file_uploaded';
    $unplag = new plagiarism_plugin_unplag();
    return $unplag->event_handler($eventdata);
}
function unplag_event_files_done($eventdata) {
    $eventdata->eventtype = 'files_done';
    $unplag = new plagiarism_plugin_unplag();
    return $unplag->event_handler($eventdata);
}

function unplag_event_content_uploaded($eventdata) {
    $eventdata->eventtype = 'content_uploaded';
    $unplag = new plagiarism_plugin_unplag();
    return $unplag->event_handler($eventdata);
}

function unplag_event_content_done($eventdata) {
    $eventdata->eventtype = 'content_done';
    $unplag = new plagiarism_plugin_unplag();
    return $unplag->event_handler($eventdata);
}

function unplag_event_assessable_submitted($eventdata) {
    $eventdata->eventtype = 'assessable_submitted';
    $unplag = new plagiarism_plugin_unplag();
    return $unplag->event_handler($eventdata);
}

function unplag_event_mod_created($eventdata) {
    $result = true;
        // A new module has been created - this is a generic event that is called for all module types
        // make sure you check the type of module before handling if needed.

    return $result;
}

function unplag_event_mod_updated($eventdata) {
    $result = true;
        // A module has been updated - this is a generic event that is called for all module types
        // make sure you check the type of module before handling if needed.

    return $result;
}

function unplag_event_mod_deleted($eventdata) {
    $result = true;
        // A module has been deleted - this is a generic event that is called for all module types
        // make sure you check the type of module before handling if needed.

    return $result;
}

function unplag_supported_events() {
    $supportedevents = array('file_uploaded', 'files_done', 'content_uploaded', 'content_done', 'assessable_submitted');
    return $supportedevents;
}

/**
 * Adds the list of plagiarism settings to a form.
 *
 * @param object $mform - Moodle form object.
 */
function unplag_get_form_elements($mform) {
    $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
    $tiioptions = array(0 => get_string("never"), 1 => get_string("always"),
                        2 => get_string("showwhenclosed", "plagiarism_unplag"));
    $unplagdraftoptions = array(
            PLAGIARISM_UNPLAG_DRAFTSUBMIT_IMMEDIATE => get_string("submitondraft", "plagiarism_unplag"),
            PLAGIARISM_UNPLAG_DRAFTSUBMIT_FINAL => get_string("submitonfinal", "plagiarism_unplag")
            );

    $mform->addElement('header', 'plagiarismdesc', get_string('unplag', 'plagiarism_unplag'));
    $mform->addElement('select', 'use_unplag', get_string("useunplag", "plagiarism_unplag"), $ynoptions);
    $mform->addElement('text', 'unplag_receiver', get_string("unplag_receiver", "plagiarism_unplag"), array('size' => 40));
    $mform->addHelpButton('unplag_receiver', 'unplag_receiver', 'plagiarism_unplag');
    $mform->setType('unplag_receiver', PARAM_TEXT);
    $mform->addElement('select', 'unplag_show_student_score',
                       get_string("unplag_show_student_score", "plagiarism_unplag"), $tiioptions);
    $mform->addHelpButton('unplag_show_student_score', 'unplag_show_student_score', 'plagiarism_unplag');
    $mform->addElement('select', 'unplag_show_student_report',
                       get_string("unplag_show_student_report", "plagiarism_unplag"), $tiioptions);
    $mform->addHelpButton('unplag_show_student_report', 'unplag_show_student_report', 'plagiarism_unplag');
    if ($mform->elementExists('var4') ||
        $mform->elementExists('submissiondrafts')) {
        $mform->addElement('select', 'unplag_draft_submit',
                           get_string("unplag_draft_submit", "plagiarism_unplag"), $unplagdraftoptions);
    }
    $mform->addElement('select', 'unplag_studentemail', get_string("unplag_studentemail", "plagiarism_unplag"), $ynoptions);
    $mform->addHelpButton('unplag_studentemail', 'unplag_studentemail', 'plagiarism_unplag');
}

/**
 * Updates a unplag_files record.
 *
 * @param int $cmid - course module id
 * @param int $userid - user id
 * @param varied $identifier - identifier for this plagiarism record - hash of file, id of quiz question etc
 * @return int - id of unplag_files record
 */
function unplag_get_plagiarism_file($cmid, $userid, $file) {
    global $DB;

    $filehash = (!empty($file->identifier)) ? $file->identifier : $file->get_contenthash();
    // Now update or insert record into unplag_files.
    $plagiarismfile = $DB->get_record_sql(
                                "SELECT * FROM {plagiarism_unplag_files}
                                 WHERE cm = ? AND userid = ? AND " .
                                "identifier = ?",
                                array($cmid, $userid, $filehash));
    if (!empty($plagiarismfile)) {
            return $plagiarismfile;
    } else {
        $plagiarismfile = new stdClass();
        $plagiarismfile->cm = $cmid;
        $plagiarismfile->userid = $userid;
        $plagiarismfile->identifier = $filehash;
        $plagiarismfile->filename = (!empty($file->filename)) ? $file->filename : $file->get_filename();
        $plagiarismfile->statuscode = 'pending';
        $plagiarismfile->attempt = 0;
        $plagiarismfile->timesubmitted = time();
        if (!$pid = $DB->insert_record('plagiarism_unplag_files', $plagiarismfile)) {
            debugging("insert into unplag_files failed");
        }
        $plagiarismfile->id = $pid;
        return $plagiarismfile;
    }
}
function unplag_send_file($cmid, $userid, $file, $plagiarismsettings) {
    global $DB;
    $plagiarismfile = unplag_get_plagiarism_file($cmid, $userid, $file);

    // Check if $plagiarismfile actually needs to be submitted.
    if ($plagiarismfile->statuscode <> 'pending') {
        return true;
    }
    $filename = (!empty($file->filename)) ? $file->filename : $file->get_filename();
    if ($plagiarismfile->filename !== $filename) {
        // This is a file that was previously submitted and not sent to unplag but the filename has changed so fix it.
        $plagiarismfile->filename = $filename;
        $DB->update_record('plagiarism_unplag_files', $plagiarismfile);
    }

    // Increment attempt number.
    $plagiarismfile->attempt = $plagiarismfile->attempt++;
    $DB->update_record('plagiarism_unplag_files', $plagiarismfile);

    return unplag_send_file_to_unplag($plagiarismfile, $plagiarismsettings, $file);
}
// Function to check timesubmitted and attempt to see if we need to delay an API check.
// also checks max attempts to see if it has exceeded.
function unplag_check_attempt_timeout($plagiarismfile) {
    global $DB;
    // The first time a file is submitted we don't need to wait at all.
    if (empty($plagiarismfile->attempt) && $plagiarismfile->statuscode == 'pending') {
        return true;
    }
    $now = time();
    // Set some initial defaults.
    $submissiondelay = 15;
    $maxsubmissiondelay = 60;
    $maxattempts = 4;
    if ($plagiarismfile->statuscode == UNPLAG_STATUSCODE_ACCEPTED) {
        $submissiondelay = UNPLAG_STATUS_DELAY; // Initial delay, doubled each time a check is made until the max delay is met.
        $maxsubmissiondelay = UNPLAG_MAX_STATUS_DELAY; // Maximum time to wait between checks
        $maxattempts = UNPLAG_MAX_STATUS_ATTEMPTS; // Maximum number of times to try and send a submission.
    }
    $wait = $submissiondelay;
    // Check if we have exceeded the max attempts.
    if ($plagiarismfile->attempt > $maxattempts) {
        $plagiarismfile->statuscode = 'timeout';
        $DB->update_record('plagiarism_unplag_files', $plagiarismfile);
        return true; // Return true to cancel the event.
    }
    // Now calculate wait time.
    $i = 0;
    $delay = 0;
    while ($i < $plagiarismfile->attempt) {
        $delay = $submissiondelay * pow(2,$i);
        if ($delay > $maxsubmissiondelay) {
            $delay = $maxsubmissiondelay;
        }
        $wait += $delay;
        $i++;
    }
    $wait = (int)$wait * 60;
    $timetocheck = (int)($plagiarismfile->timesubmitted + $wait);
    // Calculate when this should be checked next.

    if ($timetocheck < $now) {
        return true;
    } else {
        return false;
    }
}

function unplag_send_file_to_unplag($plagiarismfile, $plagiarismsettings, $file) {
    global $CFG,$DB;
    
    require_once($CFG->dirroot.'/plagiarism/unplag/unplagapi.class.php');
    
    $api = new UnApi($plagiarismsettings['unplag_client_id'], $plagiarismsettings['unplag_api_secret']);
    $filename = (!empty($file->filename)) ? $file->filename : $file->get_filename();
    
    mtrace("sendfile".$plagiarismfile->id);
    $useremail = $DB->get_field('user', 'email', array('id' => $plagiarismfile->userid));

    $pathinfo = pathinfo($filename);
    $ext = $pathinfo['extension'];
    $filecontents = (!empty($file->filepath)) ? file_get_contents($file->filepath) : $file->get_content();
    log_message(3, 'Send Upload Request...');
    
    
    //log_message(3, 'file_contents:', $filecontents, 'Base64:', base64_encode($filecontents));
    $response = $api->UploadFile($ext, $filecontents);
    log_message(3, 'Upload Response:', $response);
    if(isset($response['result']) && $response['result'] == true){
        //if file was uploaded successfully, lets check it!
        log_message(3, 'Send Check Request...');
        $check_resp = $api->Check('web', $response['file_id']);
        log_message(3, 'Check Response:', $check_resp);
    }
    else{
        //upload failed
        $plagiarismfile->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
        $plagiarismfile->errorresponse = json_encode($response['errors']);
        
            $DB->update_record('plagiarism_unplag_files', $plagiarismfile);
            return true;
    }
    
    if (isset($check_resp[0]['check_id'])) {
     
            if ($check_resp['result']) {
                $plagiarismfile->attempt = 0; // Reset attempts for status checks.
                $plagiarismfile->check_id = $check_resp[0]['check_id'];
                $plagiarismfile->statuscode = UNPLAG_STATUSCODE_ACCEPTED;
            } else {
                $plagiarismfile->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
                $plagiarismfile->errorresponse = implode(',',array_keys($check_resp['errors']));
            }
            
            //$plagiarismfile->statuscode = 500;
            $DB->update_record('plagiarism_unplag_files', $plagiarismfile);
            return true;
        
    }
    // Invalid response returned - increment attempt value and return false to allow this to be called again.
    $plagiarismfile->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
    $plagiarismfile->errorresponse = '{"unknown":"Unknown error."}';
    $DB->update_record('plagiarism_unplag_files', $plagiarismfile);
    return true;
}



/**
 * Used to obtain similarity scores from UNPLAG for submitted files.
 *
 * @param object $plagiarismsettings - from a call to plagiarism_get_settings.
 *
 */
function unplag_get_scores($plagiarismsettings) {
    global $DB;

    mtrace("getting UNPLAG similarity scores");
    log_message(3, 'Trying to get results');
    // Get all files set that have been submitted.
    $files = $DB->get_recordset('plagiarism_unplag_files', array('statuscode' => UNPLAG_STATUSCODE_ACCEPTED));
    log_message(3, 'Files to get results:', $files);
    foreach ($files as $plagiarismfile) {
        unplag_get_score($plagiarismsettings, $plagiarismfile);
    }
    $files->close();
}

function unplag_get_score($plagiarismsettings, $plagiarismfile, $force = false) {
    global $CFG, $DB;
    
    require_once($CFG->dirroot.'/plagiarism/unplag/unplagapi.class.php');
    $api = new UnApi($plagiarismsettings['unplag_client_id'], $plagiarismsettings['unplag_api_secret']);
    $results = $api->GetResults($plagiarismfile->check_id);
    log_message(3, 'Get Check results:', $results);
       
            if ($results['checks_results'][0][0]['progress']==100) {//check finished
             
                    $plagiarismfile->statuscode = UNPLAG_STATUSCODE_PROCESSED;
                
                    $plagiarismfile->progress = 100;
                    $plagiarismfile->reporturl = '#';
                    $plagiarismfile->similarityscore = (int)$results['checks_results'][0][0]['similarity'];
                    $plagiarismfile->optout = (string) 'library/viewer/pdf_report/'.$plagiarismfile->check_id.'?share_token='.$results['checks_results'][0][0]['share_token'];
                    // Now send e-mail to user.
                    $emailstudents = $DB->get_field('plagiarism_unplag_config', 'value',
                                                    array('cm' => $plagiarismfile->cm, 'name' => 'unplag_studentemail'));
                    if (!empty($emailstudents)) {
                        $unplag = new plagiarism_plugin_unplag();
                        $unplag->unplag_send_student_email($plagiarismfile);
                    }
                
            } else {//check not finished
                $plagiarismfile->progress = $results['checks_results'][0][0]['progress'];
            }
        

    $plagiarismfile->attempt = $plagiarismfile->attempt + 1;
    $DB->update_record('plagiarism_unplag_files', $plagiarismfile);
    return $plagiarismfile;
}

// Helper function to save multiple db calls.
function unplag_cm_use($cmid) {
    global $DB;
    static $useunplag = array();
    if (!isset($useunplag[$cmid])) {
        $pvalues = $DB->get_records_menu('plagiarism_unplag_config', array('cm' => $cmid), '', 'name,value');
        if (!empty($pvalues['use_unplag'])) {
            $useunplag[$cmid] = $pvalues;
        } else {
            $useunplag[$cmid] = false;
        }
    }
    return $useunplag[$cmid];
}

/**
 * Function that returns the name of the css class to use for a given similarity score.
 * @param integer $score - the similarity score
 * @return string - string name of css class
 */
function unplag_get_css_rank ($score) {
    $rank = "none";
    if ($score > 90) {
        $rank = "1";
    } else if ($score > 80) {
        $rank = "2";
    } else if ($score > 70) {
        $rank = "3";
    } else if ($score > 60) {
        $rank = "4";
    } else if ($score > 50) {
        $rank = "5";
    } else if ($score > 40) {
        $rank = "6";
    } else if ($score > 30) {
        $rank = "7";
    } else if ($score > 20) {
        $rank = "8";
    } else if ($score > 10) {
        $rank = "9";
    } else if ($score >= 0) {
        $rank = "10";
    }

    return "rank$rank";
}

// Function to check for invalid event_handlers.
function unplag_check_event_handlers() {
    global $DB, $CFG;
    $invalidhandlers = array();
    $eventhandlers = $DB->get_records('events_handlers');
    foreach ($eventhandlers as $handler) {
        $function = unserialize($handler->handlerfunction);

        if (is_callable($function)) { // This function is fine.
            continue;
        } else if (file_exists($CFG->dirroot.$handler->handlerfile)) {
            include_once($CFG->dirroot.$handler->handlerfile);
            if (is_callable($function)) { // This function is fine.
                continue;
            }
        }
        $invalidhandlers[] = $handler; // This function can't be found.
    }
    return $invalidhandlers;
}


function unplag_reset_file($id) {
    global $DB, $CFG;
    $plagiarismfile = $DB->get_record('plagiarism_unplag_files', array('id' => $id), '*', MUST_EXIST);
    if ($plagiarismfile->statuscode == UNPLAG_STATUSCODE_PROCESSED ||
        $plagiarismfile->statuscode == UNPLAG_STATUSCODE_ACCEPTED) { // Sanity Check.
        return true;
    }
    // Set some new values.
    $plagiarismfile->statuscode = 'pending';
    $plagiarismfile->attempt = 0;
    $plagiarismfile->timesubmitted = time();

    $cm = get_coursemodule_from_id('', $plagiarismfile->cm);
    $modulecontext = context_module::instance($plagiarismfile->cm);
    $fs = get_file_storage();
    if ($cm->modname == 'assignment') {
        $submission = $DB->get_record('assignment_submissions', array('assignment' => $cm->instance,
                                                                      'userid' => $plagiarismfile->userid));
        $files = $fs->get_area_files($modulecontext->id, 'mod_assignment', 'submission', $submission->id);
        foreach ($files as $file) {
            if ($file->get_contenthash() == $plagiarismfile->identifier) {
                $DB->update_record('plagiarism_unplag_files', $plagiarismfile); // Update before trying to send again.
                return unplag_send_file($plagiarismfile->cm, $plagiarismfile->userid, $file,
                                        plagiarism_plugin_unplag::get_settings());
            }
        }
    } else if ($cm->modname == 'assign') {
        require_once($CFG->dirroot.'/mod/assign/locallib.php');
        $assign = new assign($modulecontext, null, null);
        $submissionplugins = $assign->get_submission_plugins();

        $dbparams = array('assignment' => $assign->get_instance()->id, 'userid' => $plagiarismfile->userid);
        $submissions = $DB->get_records('assign_submission', $dbparams);
        foreach ($submissions as $submission) {
            foreach ($submissionplugins as $submissionplugin) {
                $component = $submissionplugin->get_subtype().'_'.$submissionplugin->get_type();
                $fileareas = $submissionplugin->get_file_areas();
                foreach ($fileareas as $filearea => $name) {
                    $files = $fs->get_area_files(
                        $assign->get_context()->id,
                        $component,
                        $filearea,
                        $submission->id,
                        "timemodified",
                        false
                    );
                    foreach ($files as $file) {
                        if ($file->get_contenthash() == $plagiarismfile->identifier) {
                            $DB->update_record('plagiarism_unplag_files', $plagiarismfile); // Update before trying to send again.
                            return unplag_send_file($plagiarismfile->cm, $plagiarismfile->userid, $file,
                                                    plagiarism_plugin_unplag::get_settings());
                        }
                    }
                }
            }
        }

    } else if ($cm->modname == 'workshop') {
        require_once($CFG->dirroot.'/mod/workshop/locallib.php');
        $cm     = get_coursemodule_from_id('workshop', $plagiarismfile->cm, 0, false, MUST_EXIST);
        $workshop = $DB->get_record('workshop', array('id' => $cm->instance), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $workshop = new workshop($workshop, $cm, $course);
        $submissions = $workshop->get_submissions($plagiarismfile->userid);
        foreach ($submissions as $submission) {
            $files = $fs->get_area_files($workshop->context->id, 'mod_workshop', 'submission_attachment', $submission->id);
            foreach ($files as $file) {
                if ($file->get_contenthash() == $plagiarismfile->identifier) {
                    $DB->update_record('plagiarism_unplag_files', $plagiarismfile); // Update before trying to send again.
                    return unplag_send_file($plagiarismfile->cm, $plagiarismfile->userid, $file,
                                            plagiarism_plugin_unplag::get_settings());
                }
            }
        }
    } else if ($cm->modname == 'forum') {
        require_once($CFG->dirroot.'/mod/forum/lib.php');
        $cm     = get_coursemodule_from_id('forum', $plagiarismfile->cm, 0, false, MUST_EXIST);
        $posts = forum_get_user_posts($cm->instance, $plagiarismfile->userid);
        foreach ($posts as $post) {
            $files = $fs->get_area_files($modulecontext->id, 'mod_forum', 'attachment', $post->id, "timemodified", false);
            foreach ($files as $file) {
                if ($file->get_contenthash() == $plagiarismfile->identifier) {
                    $DB->update_record('plagiarism_unplag_files', $plagiarismfile); // Update before trying to send again.
                    return unplag_send_file($plagiarismfile->cm, $plagiarismfile->userid, $file,
                                            plagiarism_plugin_unplag::get_settings());
                }
            }
        }

    }
}


function log_message() {
        global $CFG;
        $args = func_get_args();
        $level = array_shift($args);

        
        $arr = [];
        foreach($args as $data) {
            if(is_bool($data)) $data = $data ? 'true' : 'false';
            elseif($data instanceof Exception) {
                $data = (string)$data;
            }
            elseif(is_object($data) || is_array($data)) {
                ob_start();
                var_dump($data);
                $data = ob_get_clean();
            }
            $arr[] = $data;
        }
        
        switch($level) {
            case -1: 
                $level_str = 'UNHANDLED';
                break;
            case 1: 
                $level_str = 'ERROR';
                break;
            case 2: 
                $level_str = 'WARNING'; 
                break;
            case 3: 
                $level_str = 'DEBUG';
                break;
            default: 
                $level_str = '?';
        }
        
        $res = file_put_contents($CFG->dirroot.'/plagiarism/unplag/log.txt', "\n" . date('[d.m.Y H:i:s]') . "[$level_str] " . implode("\n", $arr), FILE_APPEND);
        if(!$res) return false;

        
        return $res;
    }