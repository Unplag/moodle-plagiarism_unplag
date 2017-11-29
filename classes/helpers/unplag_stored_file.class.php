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
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\helpers;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_stored_file
 *
 * @package   plagiarism_unplag\classes\helpers
 * @namespace plagiarism_unplag\classes\helpers
 *
 */
class unplag_stored_file extends \stored_file {

    /**
     * @param \stored_file $file
     * @return string
     */
    public static function get_protected_pathname(\stored_file $file) {
        return $file->get_pathname_by_contenthash();
    }

    /**
     * @param $id
     *
     * @return array
     */
    public static function get_plagiarism_file_childs_by_id($id) {
        global $DB;

        return $DB->get_records_list(UNPLAG_FILES_TABLE, 'parent_id', [$id]);
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public static function get_plagiarism_file_by_id($id) {
        global $DB;

        return $DB->get_record(UNPLAG_FILES_TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public static function find_plagiarism_file_by_id($id) {
        global $DB;

        return $DB->get_record(UNPLAG_FILES_TABLE, ['id' => $id], '*');
    }

    /**
     * @param $identifier
     * @return mixed
     */
    public static function get_plagiarism_file_by_identifier($identifier) {
        global $DB;

        return $DB->get_record(UNPLAG_FILES_TABLE, ['identifier' => $identifier], '*', MUST_EXIST);
    }
}