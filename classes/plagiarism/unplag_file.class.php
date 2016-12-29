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
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\plagiarism;

use plagiarism_unplag\classes\exception\unplag_exception;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_plagiarism_entity;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_file
 *
 * @package   plagiarism_unplag\classes\plagiarism
 * @namespace plagiarism_unplag\classes\plagiarism
 *
 */
class unplag_file extends unplag_plagiarism_entity {
    /**
     * @var \stored_file
     */
    private $file;

    /**
     * unplag_file constructor.
     *
     * @param unplag_core  $core
     * @param \stored_file $file
     *
     * @throws unplag_exception
     */
    public function __construct(unplag_core $core, \stored_file $file) {
        if (!$file) {
            throw new unplag_exception('Invalid argument file');
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

        $uploadedfileresponse = $this->upload();
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

            $filedata = array(
                'cm'         => $this->cmid(),
                'userid'     => $this->userid(),
                'identifier' => $this->stored_file()->get_pathnamehash(),
            );

            if ($this->core->is_teamsubmission_mode()) {
                unset($filedata['userid']);
            }

            // Now update or insert record into unplag_files.
            $plagiarismfile = $DB->get_record(UNPLAG_FILES_TABLE, $filedata);

            if (empty($plagiarismfile)) {
                $plagiarismfile = $this->new_plagiarismfile(array(
                    'cm'         => $this->cmid(),
                    'userid'     => $this->userid(),
                    'identifier' => $this->stored_file()->get_pathnamehash(),
                    'filename'   => $this->stored_file()->get_filename(),
                ));

                if (\plagiarism_unplag::is_archive($this->stored_file())) {
                    $plagiarismfile->type = unplag_plagiarism_entity::TYPE_ARCHIVE;
                }

                if (!$pid = $DB->insert_record(UNPLAG_FILES_TABLE, $plagiarismfile)) {
                    debugging("INSERT INTO {UNPLAG_FILES_TABLE}");
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
     * @return mixed
     */
    private function upload() {
        global $USER;

        $format = 'html';
        if ($source = $this->stored_file()->get_source()) {
            $format = pathinfo($source, PATHINFO_EXTENSION);
        }

        $filename = $this->stored_file()->get_filename();

        return unplag_api::instance()->upload_file(
            $this->stored_file()->get_content(),
            $filename,
            $format,
            $this->cmid(),
            unplag_core::get_external_token($USER)
        );
    }
}
