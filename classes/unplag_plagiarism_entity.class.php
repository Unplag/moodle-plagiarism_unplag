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
use plagiarism_unplag\classes\helpers\unplag_stored_file;

/**
 * Class unplag_plagiarism_entity
 *
 * @package plagiarism_unplag\classes
 */
abstract class unplag_plagiarism_entity {

    const TYPE_ARCHIVE = 'archive';
    const TYPE_DOCUMENT = 'document';

    /** @var unplag_core */
    protected $core;
    /** @var \stdClass */
    protected $plagiarismfile;

    /**
     * @return object
     */
    abstract public function upload_file_on_unplag_server();

    /**
     * @return object
     */
    abstract public function get_internal_file();

    /**
     * @return integer
     */
    protected function cmid() {
        return $this->core->cmid;
    }

    /**
     * @return integer
     */
    protected function userid() {
        return $this->core->userid;
    }

    /**
     * @param \stdClass $response
     *
     * @return bool
     */
    protected function store_file_errors(\stdClass $response) {
        global $DB;

        $plagiarismfile = $this->get_internal_file();
        $plagiarismfile->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
        $plagiarismfile->errorresponse = json_encode($response->errors);

        if ($plagiarismfile->parent_id) {
            $parentplagiarismfile = unplag_stored_file::get_unplag_file($plagiarismfile->parent_id);
            $parentplagiarismfile->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
            $parentplagiarismfile->errorresponse = json_encode($response->errors);

            $DB->update_record(UNPLAG_FILES_TABLE, $parentplagiarismfile);
        }

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
    protected function update_file_accepted($check) {
        global $DB;

        $plagiarismfile = $this->get_internal_file();
        $plagiarismfile->attempt = 0; // Reset attempts for status checks.
        $plagiarismfile->check_id = $check->id;
        $plagiarismfile->statuscode = UNPLAG_STATUSCODE_ACCEPTED;
        $plagiarismfile->errorresponse = null;

        return $DB->update_record(UNPLAG_FILES_TABLE, $plagiarismfile);
    }
}