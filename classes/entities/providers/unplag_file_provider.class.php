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

use plagiarism_unplag\classes\helpers\unplag_check_helper;
use plagiarism_unplag\classes\services\storage\unplag_file_state;
use plagiarism_unplag\classes\unplag_plagiarism_entity;

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
     * Set files to error state by pathnamehash
     *
     * @param string $pathnamehash
     * @param string $reason
     */
    public static function to_error_state_by_pathnamehash($pathnamehash, $reason) {
        global $DB;

        $files = $DB->get_recordset(UNPLAG_FILES_TABLE, ['identifier' => $pathnamehash], 'id asc', '*');
        foreach ($files as $plagiarismfile) {
            self::to_error_state($plagiarismfile, $reason);
        }
        $files->close(); // Don't forget to close the recordset!
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

    /**
     * Get all frozen documents fron database
     *
     * @return array
     */
    public static function get_frozen_files() {
        global $DB;

        $querywhere = "(state <> '"
            . unplag_file_state::CHECKED
            . "'AND state <> '"
            . unplag_file_state::HAS_ERROR
            . "') AND UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) > timesubmitted"
            . " AND external_file_uuid IS NOT NULL";

        return $DB->get_records_select(
            UNPLAG_FILES_TABLE,
            $querywhere
        );
    }

    /**
     * Get all frozen archive
     *
     * @return array
     */
    public static function get_frozen_archive() {
        global $DB;

        $querywhere = "(state <> '"
            . unplag_file_state::CHECKED
            . "'AND state <> '"
            . unplag_file_state::HAS_ERROR
            . "'
            ) AND UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) > timesubmitted "
            . "AND type = '"
            . unplag_plagiarism_entity::TYPE_ARCHIVE
            . "'";

        return $DB->get_records_select(
            UNPLAG_FILES_TABLE,
            $querywhere
        );
    }

    /**
     * Update frozen documents in database
     *
     * @param \stdClass $dbobjectfile
     * @param \stdClass $apiobjectcheck
     */
    public static function update_frozen_check($dbobjectfile, $apiobjectcheck) {
        if (is_null($dbobjectfile->check_id)) {
            $dbobjectfile->check_id = $apiobjectcheck->id;
        }
        if (is_null($dbobjectfile->external_file_id)) {
            $dbobjectfile->external_file_id = $apiobjectcheck->file_id;
        }
        unplag_check_helper::check_complete($dbobjectfile, $apiobjectcheck);
    }

    /**
     * Delete plagiarism files by id array
     *
     * @param array $ids
     */
    public static function delete_by_ids($ids) {
        global $DB;

        if (empty($ids)) {
            return;
        }

        $allrecordssql = implode(',', $ids);
        $DB->delete_records_select(UNPLAG_FILES_TABLE, "id IN ($allrecordssql) OR parent_id IN ($allrecordssql)");
    }
}