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
 * unplag_plagiarism_entity.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes;

require_once(dirname(__FILE__) . '/../constants.php');

/**
 * Class unplag_plagiarism_entity
 * @package plagiarism_unplag\classes
 */
class unplag_plagiarism_entity {
    /** @var unplag_core */
    private $core;
    /** @var \stdClass */
    private $plagiarismfile;

    /**
     * unplag_plagiarism_entity constructor.
     *
     * @param unplag_core  $core
     * @param \stored_file $file
     *
     * @throws UnplagException
     */
    public function __construct(unplag_core $core, \stored_file $file) {
        if (!$file) {
            throw new UnplagException('Invalid argument file');
        }

        $this->core = $core;
        $this->file = $file;
    }

    /**
     * @return object
     */
    public function upload_file_on_unplag_server() {
        global $DB;

        $internalfile = $this->get_internal_file();

        if (isset($internalfile->external_file_id)) {
            return $internalfile;
        }

        // Check if $internalfile actually needs to be submitted.
        if ($internalfile->statuscode !== UNPLAG_STATUSCODE_PENDING) {
            return $internalfile;
        }

        // Increment attempt number.
        $internalfile->attempt++;

        $uploadedfileresponse = unplag_api::instance()->upload_file($this->stored_file());
        if ($uploadedfileresponse->result) {
            $internalfile->external_file_id = $uploadedfileresponse->file->id;
            $DB->update_record(UNPLAG_FILES_TABLE, $internalfile);
        } else {
            $this->store_file_errors($uploadedfileresponse);
        }

        return $internalfile;
    }

    /**
     * @return object
     */
    public function get_internal_file() {
        global $DB;

        if ($this->plagiarismfile) {
            return $this->plagiarismfile;
        }

        $plagiarismfile = null;
        try {
            $filehash = $this->stored_file()->get_pathnamehash();
            // Now update or insert record into unplag_files.
            $plagiarismfile = $DB->get_record(UNPLAG_FILES_TABLE, [
                'cm'         => $this->cmid(),
                'userid'     => $this->userid(),
                'identifier' => $filehash,
            ]);

            if (empty($plagiarismfile)) {
                $plagiarismfile = new \stdClass();
                $plagiarismfile->cm = $this->cmid();
                $plagiarismfile->userid = $this->userid();
                $plagiarismfile->identifier = $filehash;
                $plagiarismfile->filename = $this->stored_file()->get_filename();
                $plagiarismfile->statuscode = UNPLAG_STATUSCODE_PENDING;
                $plagiarismfile->attempt = 0;
                $plagiarismfile->progress = 0;
                $plagiarismfile->timesubmitted = time();

                if (!$pid = $DB->insert_record(UNPLAG_FILES_TABLE, $plagiarismfile)) {
                    debugging("insert into {UNPLAG_FILES_TABLE}");
                }

                $plagiarismfile->id = $pid;
            }
        } catch (\Exception $ex) {
            print_error($ex->getMessage());
        }

        $this->plagiarismfile = $plagiarismfile;

        return $this->plagiarismfile;
    }

    /**
     * @return \stored_file
     */
    public function stored_file() {
        return $this->file;
    }

    /**
     * @return integer
     */
    private function cmid() {
        return $this->core->cmid;
    }

    /**
     * @return integer
     */
    private function userid() {
        return $this->core->userid;
    }

    /**
     * @param \stdClass $response
     *
     * @return bool
     */
    private function store_file_errors(\stdClass $response) {
        global $DB;

        $plagiarismfile = $this->get_internal_file();
        $plagiarismfile->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
        $plagiarismfile->errorresponse = json_encode($response->errors);

        return $DB->update_record(UNPLAG_FILES_TABLE, $plagiarismfile);
    }

    /**
     * @param \stdClass $checkresp
     */
    public function handle_check_response(\stdClass $checkresp) {
        if ($checkresp->result === true) {
            $this->update_file_accepted($checkresp->check);
        } else {
            $this->store_file_errors($checkresp);
        }
    }

    /**
     * @param $check
     *
     * @return bool
     */
    private function update_file_accepted($check) {
        global $DB;

        $plagiarismfile = $this->get_internal_file();
        $plagiarismfile->attempt = 0; // Reset attempts for status checks.
        $plagiarismfile->check_id = $check->id;
        $plagiarismfile->statuscode = UNPLAG_STATUSCODE_ACCEPTED;
        $plagiarismfile->errorresponse = null;

        return $DB->update_record(UNPLAG_FILES_TABLE, $plagiarismfile);
    }
}