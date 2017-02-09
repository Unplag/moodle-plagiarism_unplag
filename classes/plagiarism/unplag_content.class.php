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
 * Class unplag_content
 *
 * @package   plagiarism_unplag\classes\plagiarism
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
     * @param string      $content
     * @param             $name
     * @param null        $ext
     * @param null        $parentid
     *
     * @throws unplag_exception
     */
    public function __construct(unplag_core $core, $content = null, $name, $ext = null, $parentid = null) {
        if (!$ext) {
            $ext = 'html';
        }

        $this->core = $core;
        $this->name = $name;
        $this->ext = $ext;
        $this->parentid = $parentid;

        $this->set_content($content);
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

        $uploadedfileresponse = unplag_api::instance()->upload_file(
            $this->get_content(),
            $this->name,
            $this->ext,
            $this->cmid()
        );

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
                'identifier' => sha1($this->name . $this->cmid() . UNPLAG_DEFAULT_FILES_AREA . $this->parentid),
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
                    'identifier' => $filedata['identifier'],
                    'filename'   => $this->name,
                ));

                if ($this->parentid) {
                    $plagiarismfile->parent_id = $this->parentid;
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
     * @return string
     */
    public function get_content() {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function set_content($content) {
        $this->content = $content;
    }
}