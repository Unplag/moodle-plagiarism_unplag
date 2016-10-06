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

use plagiarism_unplag\classes\exception\UnplagException;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_plagiarism_entity;

/**
 * Class unplag_content
 *
 * @package plagiarism_unplag\classes\plagiarism
 * @namespace plagiarism_unplag\classes\plagiarism
 *
 */
class unplag_content extends unplag_plagiarism_entity {

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $ext;

    /**
     * @var int
     */
    private $parentid;

    /**
     * unplag_content constructor.
     *
     * @param unplag_core $core
     * @param $content
     * @param $name
     * @param null $ext
     * @param null $parentid
     * @throws UnplagException
     */
    public function __construct(unplag_core $core, $content, $name, $ext = null, $parentid = null) {
        if (!$content) {
            throw new UnplagException('Invalid argument content');
        }

        if ($ext) {
            $ext = 'html';
        }

        $this->core = $core;
        $this->content = $content;
        $this->name = $name;
        $this->ext = $ext;
        $this->parentid = $parentid;
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

        $uploadedfileresponse = unplag_api::instance()->upload_file($this->content, $this->name, $this->ext);
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

            $identifier = md5($this->name);
            $filedata = array(
                    'cm' => $this->cmid(),
                    'userid' => $this->userid(),
                    'identifier' => $identifier,
            );

            // Now update or insert record into unplag_files.
            $plagiarismfile = $DB->get_record(UNPLAG_FILES_TABLE, $filedata);

            if (empty($plagiarismfile)) {
                $plagiarismfile = new \stdClass();
                if($this->parentid){
                    $plagiarismfile->parent_id = $this->parentid;
                }
                $plagiarismfile->cm = $filedata['cm'];
                $plagiarismfile->userid = $filedata['userid'];
                $plagiarismfile->identifier = $filedata['identifier'];
                $plagiarismfile->filename = $this->name;
                $plagiarismfile->statuscode = UNPLAG_STATUSCODE_PENDING;
                $plagiarismfile->attempt = 0;
                $plagiarismfile->progress = 0;
                $plagiarismfile->timesubmitted = time();

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
}