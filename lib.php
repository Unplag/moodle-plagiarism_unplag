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
 * @author     Mikhail Grinenko <m.grinenko@p1k.co.uk>
 * @copyright  UKU Group, LTD, https://www.unplag.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unplag\classes\UnApi;


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

// Get global class.
global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot.'/plagiarism/unplag/classes/unplagapi.class.php');

// There is a new UNPLAG API - The Integration Service - we only currently use this to verify the receiver address.
// If we convert the existing calls to send file/get score we should move this to a config setting.

define('UNPLAG_MAX_SUBMISSION_ATTEMPTS', 6); // Maximum number of times to try and send a submission to UNPLAG.
define('UNPLAG_MAX_SUBMISSION_DELAY', 60); // Maximum time to wait between submissions (defined in minutes).
define('UNPLAG_SUBMISSION_DELAY', 15); // Initial delay, doubled each time a check is made until the max_submission_delay is met.
define('UNPLAG_MAX_STATUS_ATTEMPTS', 10); // Maximum number of times to try and obtain the status of a submission.
define('UNPLAG_MAX_STATUS_DELAY', 1440); // Maximum time to wait between checks (defined in minutes).
define('UNPLAG_STATUS_DELAY', 30); // Initial delay, doubled each time a check is made until the max_status_delay is met.
define('UNPLAG_STATUSCODE_PROCESSED', '200');
define('UNPLAG_STATUSCODE_ACCEPTED', '202');
define('UNPLAG_STATUSCODE_UNSUPPORTED', '415');
define('UNPLAG_STATUSCODE_INVALID_RESPONSE', '613'); // Invalid response received from UNPLAG.

// Url to external xml that states UNPLAGS allowed file type list.
define('UNPLAG_DOMAIN', 'https://unplag.com/');


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
                     'unplag_draft_submit', 'unplag_studentemail');
    }
    /**
     * Hook to allow plagiarism specific information to be displayed beside a submission.
     * @param array  $linkarraycontains all relevant information for the plugin to generate a link.
     * @return string
     *
     */
    public function get_links($linkarray) {
        //online submission view is unavailable due https://tracker.moodle.org/browse/MDL-40460
        global $COURSE, $OUTPUT, $CFG, $PAGE;
        
        
  
        
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
            $rank =self::unplag_get_css_rank($results['score']);
            $output .= '<span class="un_report">';
            if (!empty($results['optoutlink']) || !empty($results['score'])) {
                // User is allowed to view the report.
                // Score is contained in report, so they can see the score too.
                //$output .= ' <a target="_blank" class="un_tooltip" href="' . UNPLAG_DOMAIN.$results['optoutlink'] . '" title="'.get_string('plagiarism', 'plagiarism_unplag').'">';
                $output .= '<img  width="32" height="32" src="'.$OUTPUT->pix_url('unplag', 'plagiarism_unplag').'"> ';
                
                //$output .= '</a>';
            } 
            if ($results['score'] !== '') {
                // User is allowed to view only the score.
                $output .= get_string('similarity', 'plagiarism_unplag') . ': ';
                $output .= '<span class="'.$rank.'">'.$results['score'].'%</span>';
               
            }
            
            if (!empty($results['optoutlink'])) {
                // Display opt-out link.
                $output .= '&nbsp;<span class"plagiarismoptout">' .
                        '<a title="'.get_string('report', 'plagiarism_unplag').'" href="' . UNPLAG_DOMAIN.$results['optoutlink'] . '" target="_blank">' .
                        '<img class="un_tooltip" src="'.$OUTPUT->pix_url('link', 'plagiarism_unplag').'">'.
                        '</a></span>';
            }
            /*if (!empty($results['renamed'])) {
                $output .= $results['renamed'];
            }*/
            $output .= '</span>';
        } elseif ($results['statuscode'] == UNPLAG_STATUSCODE_ACCEPTED || $results['statuscode'] == 'pending') {
            // Now add JS to validate receiver indicator using Ajax.
    
            $jsmodule = array(
                'name' => 'plagiarism_unplag',
                'fullpath' => '/plagiarism/unplag/ajax.js',
                'requires' => array('json'),
            );
            $PAGE->requires->js_init_call('M.plagiarism_unplag.init', array($linkarray['cmid']), true, $jsmodule);
            
            $output .= '<span class="un_report">'.
                       '<img  class="'.$results['pid'].' un_progress un_tooltip" src="'.$OUTPUT->pix_url('scan', 'plagiarism_unplag') .
                        '" alt="'.get_string('processing', 'plagiarism_unplag').'" '.
                        '" title="'.get_string('processing', 'plagiarism_unplag').'" file_id="'.$results['pid'].'" /> '.
                        get_string('progress', 'plagiarism_unplag').' : <span file_id="'.$results['pid'].'" class="un_progress_val" >'.intval($results['progress']).'%</span></span>';
            
        } else if ($results['statuscode'] == UNPLAG_STATUSCODE_INVALID_RESPONSE && is_array($errors) && array_key_exists('format', $errors)) {
            $output .= '<span class="un_report">'.
                       '<img class="un_tooltip" src="'.$OUTPUT->pix_url('error', 'plagiarism_unplag') .
                        '" alt="'.get_string('unsupportedfiletype', 'plagiarism_unplag').'" '.
                        '" title="'.get_string('unsupportedfiletype', 'plagiarism_unplag').'" />'.
                        '</span>';
        }  else {
            
            $title = get_string('unknownwarning', 'plagiarism_unplag');
            $reset = '';
            if (has_capability('plagiarism/unplag:resetfile', $modulecontext) &&
                !empty($results['error'])) { // This is a teacher viewing the responses.
                // Strip out some possible known text to tidy it up.
                if(is_array($errors))
                    $erroresponse = format_text(implode(',',  array_values($errors)), FORMAT_PLAIN);
                else
                    $erroresponse = get_string('unknownwarning', 'plagiarism_unplag');
                $erroresponse = str_replace('{&quot;LocalisedMessage&quot;:&quot;', '', $erroresponse);
                $erroresponse = str_replace('&quot;,&quot;Message&quot;:null}', '', $erroresponse);
                $title .= ': ' . $erroresponse;
                $url = new moodle_url('/plagiarism/unplag/reset.php', array('cmid' => $cmid, 'pf' => $results['pid'],
                                                                            'sesskey' => sesskey()));
                $reset = " <a href='$url'><img src='".$OUTPUT->pix_url('reset', 'plagiarism_unplag')."' title='".get_string('reset')."'></a>";
            }
            $output .= '<span class="un_report">'.
                       '<img class="un_tooltip" src="'.$OUTPUT->pix_url('error', 'plagiarism_unplag') .
                        '" alt="'.get_string('unknownwarning', 'plagiarism_unplag').'" '.
                        '" title="'.$title.'" />'.$reset.'</span>';
        }
        return $output;
    }
    
    public function track_progress($file_id){
        global $DB;
        $record = $DB->get_record('plagiarism_unplag_files', array('id' => $file_id));
        return array('progress' => (int)$record->progress, 'refresh' => get_string('refresh', 'plagiarism_unplag'));    
    }

    public function get_file_results($cmid, $userid, $file) {
        global $DB, $USER, $CFG;
        $plagiarismsettings = $this->get_settings();
        if (empty($plagiarismsettings)) {
            // Unplag is not enabled.
            return false;
        }
        $plagiarismvalues =self::unplag_cm_use($cmid);
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
        else if (!empty($module->duedate)) {
            $assignclosed = ($module->duedate <= $time);
        }
        else if (!empty($module->cutoffdate)) {
            $assignclosed = ($module->cutoffdate <= $time);
        }
      
        // Under certain circumstances, users are allowed to see plagiarism info
        // even if they don't have view report capability.
        if ($USER->id == $userid) {
            $selfreport = true;
            if (isset($plagiarismvalues['unplag_show_student_report']) &&
                    ($plagiarismvalues['unplag_show_student_report'] == PLAGIARISM_UNPLAG_SHOW_ALWAYS ||
                     ($plagiarismvalues['unplag_show_student_report'] == PLAGIARISM_UNPLAG_SHOW_CLOSED && $assignclosed))) {
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
        if ($plagiarismfile->statuscode == UNPLAG_STATUSCODE_ACCEPTED || $plagiarismfile->statuscode == 'pending') {
            $results['statuscode'] = UNPLAG_STATUSCODE_ACCEPTED;
            $results['progress'] = $plagiarismfile->progress;
            $results['pid'] = $plagiarismfile->id;
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
           self::unplag_get_form_elements($mform);
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

        } else { // Add plagiarism settings as hidden vars.
            foreach ($plagiarismelements as $element) {
                $mform->addElement('hidden', $element);
                $mform->setType('use_unplag', PARAM_INT);
                $mform->setType('unplag_show_student_score', PARAM_INT);
                $mform->setType('unplag_show_student_report', PARAM_INT);
                $mform->setType('unplag_draft_submit', PARAM_INT);
              
                $mform->setType('unplag_studentemail', PARAM_INT);
            }
        }
        // Now set defaults.
        foreach ($plagiarismelements as $element) {
            if (isset($plagiarismvalues[$element])) {
                $mform->setDefault($element, $plagiarismvalues[$element]);
            } else if (isset($plagiarismdefaults[$element])) {
                $mform->setDefault($element, $plagiarismdefaults[$element]);
            }
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

        $unplaguse =self::unplag_cm_use($cmid);
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
       
        // Do any scheduled task stuff.
        //unplag_update_allowed_filetypes();
        // Weird hack to include filelib correctly before allowing use in event_handler.
        require_once($CFG->libdir.'/filelib.php');
        
        if ($plagiarismsettings = $this->get_settings()) {
           self::unplag_get_scores($plagiarismsettings);
        }
    }
    /**
     * Generic handler function for all events - triggers sending of files.
     * @return boolean
     */
    static function event_handler($eventdata) {
        global $DB, $CFG;
      
       
        $plagiarismsettings = self::get_settings();
        if (!$plagiarismsettings) {
            return true;
        }
        $cmid = $eventdata['contextinstanceid'];
        $plagiarismvalues = $DB->get_records_menu('plagiarism_unplag_config', array('cm' => $cmid), '', 'name, value');
        if (empty($plagiarismvalues['use_unplag'])) {
            // Unplag not in use for this cm - return.
            return true;
        }

        // Check if the module associated with this event still exists.
        if (!$DB->record_exists('course_modules', array('id' => $cmid))) {
            return true;
        }

        if (($eventdata['component'] == 'assessable_submitted' && $eventdata['other']['submission_editable'] == false)) {
            // Assignment-specific functionality:
            // This is a 'finalize' event. No files from this event itself,
            // but need to check if files from previous events need to be submitted for processing.
         
            $result = true;
            if (isset($plagiarismvalues['unplag_draft_submit']) &&
                $plagiarismvalues['unplag_draft_submit'] == PLAGIARISM_UNPLAG_DRAFTSUBMIT_FINAL) {
                // Any files attached to previous events were not submitted.
                // These files are now finalized, and should be submitted for processing.
                if ($eventdata['component'] == 'assignsubmission_file'
                || $eventdata['component'] == 'assignsubmission_onlinetext') {
                    // Hack to include filelib so that file_storage class is available.
                    require_once("$CFG->dirroot/mod/assignment/lib.php");
                    // We need to get a list of files attached to this assignment and put them in an array, so that
                    // we can submit each of them for processing.
                    $assignmentbase = new assignment_base($cmid);
                    $submission = $assignmentbase->get_submission($eventdata['userid']);
                    $modulecontext = context_module::instance($cmid);
                    $fs = get_file_storage();
                    if ($files = $fs->get_area_files($modulecontext->id, 'mod_assignment', 'submission', $submission->id,
                                                     "timemodified", false)) {
                        foreach ($files as $file) {
                            $sendresult =self::unplag_send_file($cmid, $eventdata['userid'], $file, $plagiarismsettings);
                            $result = $result && $sendresult;
                        }
                    }
                } else if ($eventdata['component'] == 'mod_assign') {
                    require_once("$CFG->dirroot/mod/assign/locallib.php");
                    require_once("$CFG->dirroot/mod/assign/submission/file/locallib.php");

                    $modulecontext = context_module::instance($cmid);
                    $fs = get_file_storage();
                    if ($files = $fs->get_area_files($modulecontext->id, 'assignsubmission_file',
                                                     ASSIGNSUBMISSION_FILE_FILEAREA, $eventdata['objectid'], "id", false)) {
                        foreach ($files as $file) {
                            $sendresult =self::unplag_send_file($cmid, $eventdata['userid'], $file, $plagiarismsettings);
                            $result = $result && $sendresult;
                        }
                    }
                    $submission = $DB->get_record('assignsubmission_onlinetext', array('submission' => $eventdata['objectid']));
                    if (!empty($submission)) {
                        $eventdata['other']['content'] = trim(format_text($submission->onlinetext, $submission->onlineformat,
                                                               array('context' => $modulecontext)));
                        $file =self::unplag_create_temp_file($cmid, $eventdata);
                        $sendresult =self::unplag_send_file($cmid, $eventdata['userid'], $file, $plagiarismsettings);
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
        if (!empty($eventdata['other']['content'])) {
            $file =self::unplag_create_temp_file($cmid, $eventdata);
            $sendresult =self::unplag_send_file($cmid, $eventdata['userid'], $file, $plagiarismsettings);
            $result = $result && $sendresult;
            unlink($file->filepath); // Delete temp file.
        }

        // Normal situation: 1 or more assessable files attached to event, ready to be checked.
        if (!empty($eventdata['other']['pathnamehashes'])) {
            foreach ($eventdata['other']['pathnamehashes'] as $hash) {
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
                if ($eventdata['component'] == 'assignsubmission_file'
                || $eventdata['component'] == 'assignsubmission_onlinetext') {
                    require_once("$CFG->dirroot/mod/assign/locallib.php");
                    $modulecontext = context_module::instance($cmid);
                    $assign = new assign($modulecontext, false, false);
                    if (!empty($assign->get_instance()->teamsubmission)) {
                        $mygroups = groups_get_user_groups($assign->get_course()->id, $eventdata['userid']);
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
                            $previousfiles = $DB->get_records_select('plagiarism_unplag_files', $sql, array($cmid), 'id');
                            $sanitycheckusers = 10; // Search through this number of users to find a valid previous submission.
                            $i = 0;
                            foreach ($previousfiles as $pf) {
                                if ($pf->userid == $eventdata['userid']) {
                                    break; // The submission comes from this user so break.
                                }
                                // Sanity Check to make sure the user isn't in multiple groups.
                                $pfgroups = groups_get_user_groups($assign->get_course()->id, $pf->userid);
                                if (count($pfgroups) == 1) {
                                    // This user made the first valid submission so use their id when sending the file.
                                    $eventdata['userid'] = $pf->userid;
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

                $sendresult =self::unplag_send_file($cmid, $eventdata['userid'], $efile, $plagiarismsettings);
                $result = $result && $sendresult;
            }
        }
        return $result;
    }

    static function plagiarism_unplag_send_student_email($plagiarismfile) {
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
    
    static function unplag_event_file_uploaded($eventdata) {

      
            return self::event_handler($eventdata->get_data());
        }

        static function unplag_event_content_uploaded($eventdata) {

           
            return self::event_handler($eventdata->get_data());
        }



        static function unplag_event_assessable_submitted($eventdata) {
 
           
            return self::event_handler($eventdata->get_data());
        }





        static function unplag_create_temp_file($cmid, $eventdata) {
            global $CFG;
            if (!check_dir_exists($CFG->tempdir."/unplag", true, true)) {
                mkdir($CFG->tempdir."/unplag", 0700);
            }
            $filename = "content-" . $eventdata['contextid'] . "-" . $cmid . "-" . $eventdata['userid'] . ".htm";
            $filepath = $CFG->tempdir."/unplag/" . $filename;
            $fd = fopen($filepath, 'wb');   // Create if not exist, write binary.

            // Write html and body tags as it seems that Unplag doesn't works well without them.
            $content = '<html>' .
                       '<head>' .
                       '<meta charset="UTF-8">' .
                       '</head>' .
                       '<body>' .
                       $eventdata['other']['content'] .
                       '</body></html>';

            fwrite($fd, $content);
            fclose($fd);
            $file = new \stdClass();
            $file->type = "tempunplag";
            $file->filename = $filename;
            $file->timestamp = time();
            $file->identifier = sha1($eventdata['other']['content']);
            $file->filepath = $filepath;
            return $file;
        }

        

        /**
         * Adds the list of plagiarism settings to a form.
         *
         * @param object $mform - Moodle form object.
         */
        static function unplag_get_form_elements($mform) {
            $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
            $tiioptions = array(0 => get_string("never"), 1 => get_string("always"),
                                2 => get_string("showwhenclosed", "plagiarism_unplag"));
            $unplagdraftoptions = array(
                    PLAGIARISM_UNPLAG_DRAFTSUBMIT_IMMEDIATE => get_string("submitondraft", "plagiarism_unplag"),
                    PLAGIARISM_UNPLAG_DRAFTSUBMIT_FINAL => get_string("submitonfinal", "plagiarism_unplag")
                    );

            $mform->addElement('header', 'plagiarismdesc', get_string('unplag', 'plagiarism_unplag'));
            $mform->addElement('select', 'use_unplag', get_string("useunplag", "plagiarism_unplag"), $ynoptions);

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
        static function unplag_get_plagiarism_file($cmid, $userid, $file) {
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
                $plagiarismfile = new \stdClass();
                $plagiarismfile->cm = $cmid;
                $plagiarismfile->userid = $userid;
                $plagiarismfile->identifier = $filehash;
                $plagiarismfile->filename = (!empty($file->filename)) ? $file->filename : $file->get_filename();
                $plagiarismfile->statuscode = 'pending';
                $plagiarismfile->attempt = 0;
                $plagiarismfile->progress = 0;
                $plagiarismfile->timesubmitted = time();
                if (!$pid = $DB->insert_record('plagiarism_unplag_files', $plagiarismfile)) {
                    debugging("insert into unplag_files failed");
                }
                $plagiarismfile->id = $pid;
                return $plagiarismfile;
            }
        }
        static function unplag_send_file($cmid, $userid, $file, $plagiarismsettings) {
            global $DB;
            $plagiarismfile = self::unplag_get_plagiarism_file($cmid, $userid, $file);

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

            return self::unplag_send_file_to_unplag($plagiarismfile, $plagiarismsettings, $file);
        }
        // Function to check timesubmitted and attempt to see if we need to delay an API check.
        // also checks max attempts to see if it has exceeded.
        static function unplag_check_attempt_timeout($plagiarismfile) {
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

        static function unplag_send_file_to_unplag($plagiarismfile, $plagiarismsettings, $file) {
            global $CFG,$DB;

            $api = new UnApi($plagiarismsettings['unplag_client_id'], $plagiarismsettings['unplag_api_secret']);
            $filename = (!empty($file->filename)) ? $file->filename : $file->get_filename();

            mtrace("sendfile".$plagiarismfile->id);
            $useremail = $DB->get_field('user', 'email', array('id' => $plagiarismfile->userid));

            $pathinfo = pathinfo($filename);
            $ext = $pathinfo['extension'];
            $filecontents = (!empty($file->filepath)) ? file_get_contents($file->filepath) : $file->get_content();
            


           
            $response = $api->UploadFile($ext, $filecontents);
           
            if(isset($response['result']) && $response['result'] == true){
                //if file was uploaded successfully, lets check it!
              
                $check_resp = $api->Check('web', $response['file_id']);
               
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
        static function unplag_get_scores($plagiarismsettings) {
            global $DB;
            

            
            // Get all files set that have been submitted.
            $files = $DB->get_recordset('plagiarism_unplag_files', array('statuscode' => UNPLAG_STATUSCODE_ACCEPTED));
          
            foreach ($files as $plagiarismfile) {
                self::unplag_get_score($plagiarismsettings, $plagiarismfile);
            }
            $files->close();
        }

        static function unplag_get_score($plagiarismsettings, $plagiarismfile, $force = false) {
            global $CFG, $DB;
            
            $api = new UnApi($plagiarismsettings['unplag_client_id'], $plagiarismsettings['unplag_api_secret']);
            
            $results = $api->GetResults($plagiarismfile->check_id); 
           

                    if ($results['result'] && $results['checks_results'][0][0]['progress']==100) {//check finished

                            $plagiarismfile->statuscode = UNPLAG_STATUSCODE_PROCESSED;

                            $plagiarismfile->progress = 100;
                            $plagiarismfile->reporturl = '#';
                            $plagiarismfile->similarityscore = (int)$results['checks_results'][0][0]['similarity'];
                            $plagiarismfile->optout = (string) 'library/viewer/report/'.$plagiarismfile->check_id.'?share_token='.$results['checks_results'][0][0]['share_token'];
                            // Now send e-mail to user.
                            $emailstudents = $DB->get_field('plagiarism_unplag_config', 'value',
                                                            array('cm' => $plagiarismfile->cm, 'name' => 'unplag_studentemail'));
                            if (!empty($emailstudents)) {
                                $unplag = new self();
                                $unplag->plagiarism_unplag_send_student_email($plagiarismfile);
                            }

                    } 
                    elseif(!$results['result']){
                        $plagiarismfile->status = UNPLAG_STATUSCODE_INVALID_RESPONSE;
                        $plagiarismfile->errorresponse = json_encode($results['errors']);
                    }
                    else {//check not finished
                        $plagiarismfile->progress = $results['checks_results'][0][0]['progress'];
                    }


            $plagiarismfile->attempt = $plagiarismfile->attempt + 1;
            $DB->update_record('plagiarism_unplag_files', $plagiarismfile);
            return $plagiarismfile;
        }

        // Helper static function to save multiple db calls.
        static function unplag_cm_use($cmid) {
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
        static function unplag_get_css_rank ($score) {
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

       


        static function unplag_reset_file($id) {
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
                        return self::unplag_send_file($plagiarismfile->cm, $plagiarismfile->userid, $file, self::get_settings());
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
                                    return self::unplag_send_file($plagiarismfile->cm, $plagiarismfile->userid, $file,
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
                            return self::unplag_send_file($plagiarismfile->cm, $plagiarismfile->userid, $file,
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
                            return self::unplag_send_file($plagiarismfile->cm, $plagiarismfile->userid, $file,
                                                    plagiarism_plugin_unplag::get_settings());
                        }
                    }
                }

            }
        }

}