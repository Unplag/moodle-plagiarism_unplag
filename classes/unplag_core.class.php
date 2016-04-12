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

namespace plagiarism_unplag\classes;

require_once('unplag_api.class.php');

/**
 * Class unplag_core
 * @package plagiarism_unplag\classes
 */
class unplag_core {
    const UNPLAG_FILES_TABLE = 'plagiarism_unplag_files';
    const STATUSCODE_PENDING = 'pending';
    const UNPLAG_STATUSCODE_INVALID_RESPONSE = 613;
    const UNPLAG_STATUSCODE_ACCEPTED = 202;
    const UNPLAG_STATUSCODE_PROCESSED = 200;
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
     * @param $checkstatusforthisids
     * @param $resp
     */
    public static function check_real_file_progress($checkstatusforthisids, &$resp) {
        $progresses = unplag_api::instance()->get_check_progress($checkstatusforthisids);
        if ($progresses->result) {
            foreach ($progresses->progress as $id => $val) {
                $progres = $val * 100;
                $resp[$id]['progress'] = $progres;

                self::update_file_progress($id, $progres);
            }
        }
    }

    /**
     * @param $id
     * @param $progres
     */
    private static function update_file_progress($id, $progres) {
        global $DB;

        $record = $DB->get_record(unplag_core::UNPLAG_FILES_TABLE, ['check_id' => $id]);
        if ($record->progress < $progres) {
            $record->progress = $progres;

            if ($record->progress === 100) {
                $resp = unplag_api::instance()->get_check_data($id);
                self::check_complete($record, $resp->check);
            } else {
                $DB->update_record(unplag_core::UNPLAG_FILES_TABLE, $record);
            }
        }
    }

    /**
     * @param \stdClass $record
     * @param \stdClass $check
     */
    public static function check_complete(\stdClass $record, \stdClass $check) {
        global $DB;
//var_dump($check);
        $record->statuscode = self::UNPLAG_STATUSCODE_PROCESSED;
        $record->similarityscore = $check->report->similarity * 100;
        $record->reporturl = $check->report->view_url;

        $DB->update_record(unplag_core::UNPLAG_FILES_TABLE, $record);
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
     * @param \core\event\base $event
     *
     * @return \stored_file
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function create_temp_file(\core\event\base $event) {
        $filename = sprintf("content-%d-%d-%d.html", $event->contextid, $this->cmid, $this->userid);

        $filerecord = [
            'component'  => 'user',
            'filearea'   => 'draft',
            'contextid'  => $event->contextid,
            'itemid'     => mt_rand(),
            'filename'   => $filename,
            'filepath'   => '/',
            'identifier' => sha1($event->other['content']),
        ];

        $fs = get_file_storage();
        $file = $fs->create_file_from_string($filerecord, $event->other['content']);

        return $file;
    }

    /**
     * @param \stored_file $file
     *
     * @return mixed|null
     * @throws UnplagException
     */
    public function handle_uploaded_file(\stored_file $file) {
        if (!$file) {
            throw new UnplagException('Invalid argument file');
        }

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

        if ($response->result) {
            /** @var \stdClass $checkresp */
            $checkresp = unplag_api::instance()->run_check($response->file);
            if ($checkresp->result === true) {
                $this->update_check_record($plagiarismfile, $checkresp->check);
            }
        } else {
            self::store_check_errors($plagiarismfile, $response);
        }

        return $response;
    }

    /*public function create_temp_file1(\core\event\base $eventdata) {
        global $CFG;
        $tmp_path = $CFG->tempdir . "/unplag";
        if (!check_dir_exists($tmp_path, true, true)) {
            mkdir($tmp_path, 0700);
        }UNPLAG_STATUSCODE_PROCESSED

        $filename = sprintf("content-%d-%d-%d.html", $eventdata->contextid, $this->cmid, $this->userid);
        $filepath = $tmp_path . $filename;
        //Write html and body tags as it seems that Unplag doesn't works well without them.
        $content = '<html><head><meta charset="UTF-8"></head><body>' . $eventdata->other['content'] . '</body></html>';

        file_put_contents($filepath, $content);

        $file = new \stdClass();
        $file->type = "tempunplag";
        $file->filename = $filename;
        $file->timestamp = time();
        $file->identifier = sha1($eventdata->other['content']);
        $file->filepath = $filepath;

        return $file;
    }*/

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
                debugging("insert into {self::UNPLAG_FILES_TABLE}");
            }

            $plagiarismfile->id = $pid;

            return $plagiarismfile;
        }
    }

    /**
     * @param $plagiarismfile
     * @param $check
     *
     * @return bool
     */
    private function update_check_record($plagiarismfile, $check) {
        global $DB;

        $plagiarismfile->attempt = 0; // Reset attempts for status checks.
        $plagiarismfile->check_id = $check->id;
        $plagiarismfile->statuscode = self::UNPLAG_STATUSCODE_ACCEPTED;

        return $DB->update_record(self::UNPLAG_FILES_TABLE, $plagiarismfile);
    }

    /**
     * @param           $plagiarismfile
     * @param \stdClass $response
     */
    public static function store_check_errors($plagiarismfile, \stdClass $response) {
        global $DB;

        $plagiarismfile->statuscode = self::UNPLAG_STATUSCODE_INVALID_RESPONSE;
        $plagiarismfile->errorresponse = json_encode($response->errors);
        $DB->update_record(self::UNPLAG_FILES_TABLE, $plagiarismfile);
    }
}

/**
 * Class UnplagException
 * @package plagiarism_unplag\classes
 */
class UnplagException extends \Exception {
}