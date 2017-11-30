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
 * unplag_response.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\helpers;

use plagiarism_unplag\classes\entities\providers\unplag_file_provider;
use plagiarism_unplag\classes\services\storage\unplag_file_state;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_response
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class unplag_response {
    /**
     * handle_check_response
     *
     * @param \stdClass $response
     * @param \stdClass $plagiarismfile
     * @return bool
     */
    public static function handle_check_response(\stdClass $response, \stdClass $plagiarismfile) {
        if (!$response->result) {
            return self::store_errors($response->errors, $plagiarismfile);
        }

        if ($response->check->id) {
            $progress = 100 * $response->check->progress;
            if ($progress === 100) {
                return unplag_check_helper::check_complete($plagiarismfile, $response->check, $progress);
            }

            $plagiarismfile->attempt = 0; // Reset attempts for status checks.
            $plagiarismfile->check_id = $response->check->id;
            $plagiarismfile->errorresponse = null;
            $plagiarismfile->state = unplag_file_state::CHECKING;
        }

        return unplag_file_provider::save($plagiarismfile);
    }

    /**
     * process_after_upload
     *
     * @param \stdClass $response
     * @param \stdClass $plagiarismfile
     * @return bool
     */
    public static function process_after_upload(\stdClass $response, \stdClass $plagiarismfile) {
        if (!$response->result) {
            return self::store_errors($response->errors, $plagiarismfile);
        }

        $plagiarismfile->external_file_uuid = $response->file->uuid;
        if ($response->file->id) {
            return unplag_upload_helper::upload_complete($plagiarismfile, $response->file);
        }

        return unplag_file_provider::save($plagiarismfile);
    }

    /**
     * store_errors
     *
     * @param array     $errors
     * @param \stdClass $plagiarismfile
     * @return bool
     */
    public static function store_errors(array $errors, \stdClass $plagiarismfile) {
        global $DB;

        $plagiarismfile->state = unplag_file_state::HAS_ERROR;
        $plagiarismfile->errorresponse = json_encode($errors);

        $result = unplag_file_provider::save($plagiarismfile);

        if ($result && $plagiarismfile->parent_id) {
            $hasgoodchild = $DB->count_records_select(UNPLAG_FILES_TABLE, "parent_id = ? AND state not in (?)",
                [$plagiarismfile->parent_id, unplag_file_state::HAS_ERROR]
            );

            if (!$hasgoodchild) {
                $parentplagiarismfile = unplag_file_provider::get_by_id($plagiarismfile->parent_id);
                $parentplagiarismfile->state = unplag_file_state::HAS_ERROR;
                $parentplagiarismfile->errorresponse = json_encode($errors);

                unplag_file_provider::save($parentplagiarismfile);
            }
        }

        return $result;
    }
}