<?php

namespace plagiarism_unplag\classes;

require_once('unplag_api.class.php');

/**
 * Class unplag_core
 * @package plagiarism_unplag
 */
class unplag_core {
    const UNPLAG_FILES_TABLE = 'plagiarism_unplag_files';
    const STATUSCODE_PENDING = 'pending';
    /** @var  \stored_file */
    private $file;

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
     * @param \core\event\base $event
     */
    public static function event_lisiner(\core\event\base $event) {
        global $DB, $CFG;

        //mail('v.titov@p1k.co.uk', 'moodle events', print_r($event, true));
        // var_dump($event->target, $event->action, $event->eventname, $event->component, $event->get_data());
//die;
        try {
            self::validate_event($event);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }

        /*if ($event->target == 'course_module' && $event->action == 'created') {
        }*/

        if (in_array($event->component, ['assignsubmission_file', 'assignsubmission_onlinetext', 'mod_assign'])) {
            switch ($event->component) {
                case 'mod_assign':
                    require_once("$CFG->dirroot/mod/assign/locallib.php");
                    require_once("$CFG->dirroot/mod/assign/submission/file/locallib.php");
                    break;

                default:
                    require_once("$CFG->dirroot/mod/assignment/lib.php");
                    break;
            }

            /*$fs = get_file_storage();
            $modulecontext = context_module::instance($event->contextinstanceid);
            $files = $fs->get_area_files($modulecontext->id, 'assignsubmission_file', 'submission_files');
            if ($files) {
                foreach ($files as $file) {
                }
            }
            var_dump($files);
            die;
            var_dump($modulecontext->id, $event->contextinstanceid);
            die;*/
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

    /**
     * @param \core\event\base $event
     *
     * @throws \Exception
     */
    public static function validate_event(\core\event\base $event) {
        global $DB;

        $cmid = $event->contextinstanceid;

        $plagiarismvalues = $DB->get_records_menu('plagiarism_unplag_config', ['cm' => $cmid], '', 'name, value');
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
     * This function should be used to initialise settings and check if plagiarism is enabled.
     *
     * @return mixed - false if not enabled, or returns an array of relevant settings.
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
     * @param \stored_file $file
     *
     * @return bool|null
     */
    public function handle_uploaded_file(\stored_file $file) {
        global $DB;

        $this->file = $file;

        $plagiarismfile = $this->get_internal_file();
        // Check if $plagiarismfile actually needs to be submitted.
        if ($plagiarismfile->statuscode !== self::STATUSCODE_PENDING) {
            return null;
        }

        $filename = $file->get_filename();
        if ($plagiarismfile->filename !== $filename) {
            // This is a file that was previously submitted and not sent to unplag but the filename has changed so fix it.
            $plagiarismfile->filename = $filename;
        }

        // Increment attempt number.
        $plagiarismfile->attempt = $plagiarismfile->attempt++;

        $response = unplag_api::instance()->upload_file($file);
var_dump($response);die;
        if ($response->result) {
            $check_resp = unplag_api::instance()->run_check($response->file);
        } else {
            $plagiarismfile->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
            $plagiarismfile->errorresponse = json_encode($response->errors);
        }

        $DB->update_record(self::UNPLAG_FILES_TABLE, $plagiarismfile);
        die;
        die;

        return $this->send_file_to_unplag($plagiarismfile);
    }

    /**
     * @return mixed|\stdClass
     */
    private function get_internal_file() {
        global $DB;

        $filehash = $this->file->get_contenthash();
        // Now update or insert record into unplag_files.
        $plagiarismfile = $DB->get_record(self::UNPLAG_FILES_TABLE, [
            'cm'         => $this->cmid,
            'userid'     => $this->userid,
            'identifier' => $filehash,
        ]);
        /*$plagiarismfile = $DB->get_record_sql("SELECT * FROM {plagiarism_unplag_files} WHERE cm = ? AND userid = ? AND identifier = ?",
            [$this->cmid, $this->userid, $filehash]
        );*/

        if (!empty($plagiarismfile)) {
            return $plagiarismfile;
        } else {
            $plagiarismfile = new \stdClass();
            $plagiarismfile->cm = $this->cmid;
            $plagiarismfile->userid = $this->userid;
            $plagiarismfile->identifier = $filehash;
            $plagiarismfile->filename = $this->file->get_filename();
            $plagiarismfile->statuscode = self::STATUSCODE_PENDING;
            $plagiarismfile->attempt = 0;
            $plagiarismfile->progress = 0;
            $plagiarismfile->timesubmitted = time();

            if (!$pid = $DB->insert_record(self::UNPLAG_FILES_TABLE, $plagiarismfile)) {
                debugging("insert into unplag_files failed");
            }

            $plagiarismfile->id = $pid;

            return $plagiarismfile;
        }
    }

    private function send_file_to_unplag($plagiarismfile) {
        global $CFG, $DB;


        $api = new UnApi($plagiarismsettings['unplag_client_id'], $plagiarismsettings['unplag_api_secret']);
        $filename = (!empty($file->filename)) ? $file->filename : $file->get_filename();

        mtrace("sendfile" . $plagiarismfile->id);
        $useremail = $DB->get_field('user', 'email', ['id' => $plagiarismfile->userid]);

        $pathinfo = pathinfo($filename);
        $ext = $pathinfo['extension'];
        $filecontents = (!empty($file->filepath)) ? file_get_contents($file->filepath) : $file->get_content();

        $response = $api->UploadFile($ext, $filecontents);

        if (isset($response['result']) && $response['result'] == true) {
            //if file was uploaded successfully, lets check it!

            $check_resp = $api->Check('web', $response['file_id']);
        } else {
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
                $plagiarismfile->errorresponse = implode(',', array_keys($check_resp['errors']));
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
}