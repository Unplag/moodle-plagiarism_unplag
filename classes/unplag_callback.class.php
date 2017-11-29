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

use Box\Spout\Common\Exception\InvalidArgumentException;
use plagiarism_unplag\classes\helpers\unplag_check_helper;
use plagiarism_unplag\classes\helpers\unplag_response;
use plagiarism_unplag\classes\helpers\unplag_stored_file;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_callback
 *
 * @package     plagiarism_unplag\classes
 * @subpackage  plagiarism
 * @namespace   plagiarism_unplag\classes
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_callback {
    /**
     * @param \stdClass $body
     * @param string    $token
     *
     * @throws InvalidArgumentException
     *
     * @uses file_upload_success
     * @uses file_upload_error
     * @uses similarity_check_finish
     */
    public function handle(\stdClass $body, $token) {
        if (!isset($body->event_type)) {
            throw new InvalidArgumentException("Event type does't exist");
        }

        $methodname = str_replace('.', '_', strtolower($body->event_type));
        if (!method_exists($this, $methodname)) {
            throw new \LogicException('Invalid callback event type');
        }

        $this->{$methodname}($body, $token);
    }

    /**
     * @param \stdClass $body
     * @param           $token
     * @throws InvalidArgumentException
     *
     * @used-by handle
     */
    private function file_upload_success(\stdClass $body, $token) {
        if (!isset($body->file)) {
            throw new InvalidArgumentException('File data does not exist');
        }

        $internalfile = unplag_stored_file::get_plagiarism_file_by_identifier($token);

        unplag_response::process_after_upload($body, $internalfile);

        unplag_adhoc::check($internalfile);
    }

    /**
     * @param \stdClass $body
     * @param           $token
     *
     * @used-by handle
     */
    private function file_upload_error(\stdClass $body, $token) {
        $internalfile = unplag_stored_file::get_plagiarism_file_by_identifier($token);

        unplag_response::process_after_upload($body, $internalfile);
    }

    /**
     * @param \stdClass $body
     * @param           $token
     * @throws InvalidArgumentException
     *
     * @used-by handle
     */
    private function similarity_check_finish(\stdClass $body, $token) {
        if (!isset($body->check)) {
            throw new InvalidArgumentException('Check data does not exist');
        }

        $internalfile = unplag_stored_file::get_plagiarism_file_by_identifier($token);
        $progress = 100 * $body->check->progress;
        unplag_check_helper::check_complete($internalfile, $body->check, $progress);
    }
}