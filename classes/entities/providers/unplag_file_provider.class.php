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
 * unplag_file_provider.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\entities\providers;

use plagiarism_unplag\classes\services\storage\unplag_file_state;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_file_provider
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_file_provider {

    /**
     * Update plagiarism file
     *
     * @param \stdClass $file
     * @return bool
     */
    public static function save(\stdClass $file) {
        global $DB;

        return $DB->update_record(UNPLAG_FILES_TABLE, $file);
    }

    /**
     * Get plagiarism file by id
     *
     * @param int $id
     * @return mixed
     */
    public static function get_by_id($id) {
        global $DB;

        return $DB->get_record(UNPLAG_FILES_TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Find plagiarism file by id
     *
     * @param int $id
     * @return mixed
     */
    public static function find_by_id($id) {
        global $DB;

        return $DB->get_record(UNPLAG_FILES_TABLE, ['id' => $id]);
    }

    /**
     * Find plagiarism file by check id
     *
     * @param int $checkid
     * @return mixed
     */
    public static function find_by_check_id($checkid) {
        global $DB;

        return $DB->get_record(UNPLAG_FILES_TABLE, ['check_id' => $checkid]);
    }

    /**
     * Find plagiarism files by ids
     *
     * @param array $ids
     * @return array
     */
    public static function find_by_ids($ids) {
        global $DB;

        return $DB->get_records_list(UNPLAG_FILES_TABLE, 'id', $ids);
    }

    /**
     * Can start check
     *
     * @param \stdClass $plagiarismfile
     * @return bool
     */
    public static function can_start_check(\stdClass $plagiarismfile) {
        if (in_array($plagiarismfile->state,
            [unplag_file_state::UPLOADING, unplag_file_state::UPLOADED, unplag_file_state::CHECKING, unplag_file_state::CHECKED])) {
            return false;
        }

        return true;
    }

    /**
     * Set file to error state
     *
     * @param \stdClass $plagiarismfile
     * @param  string   $reason
     */
    public static function to_error_state(\stdClass $plagiarismfile, $reason) {
        $plagiarismfile->state = unplag_file_state::HAS_ERROR;
        $plagiarismfile->errorresponse = json_encode([
            ["message" => $reason],
        ]);

        self::save($plagiarismfile);
    }

    /**
     * Get file list by parent id
     *
     * @param int $parentid
     * @return array
     */
    public static function get_file_list_by_parent_id($parentid) {
        global $DB;

        return $DB->get_records_list(UNPLAG_FILES_TABLE, 'parent_id', [$parentid]);
    }
}