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
 * unplag_settings.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_settings
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_settings {
    /**
     * SENSITIVITY_SETTING_NAME
     */
    const SENSITIVITY_SETTING_NAME = 'similarity_sensitivity';
    /**
     * USE_UNPLAG
     */
    const USE_UNPLAG = 'use_unplag';
    /**
     * SHOW_STUDENT_SCORE
     */
    const SHOW_STUDENT_SCORE = 'unplag_show_student_score';
    /**
     * SHOW_STUDENT_REPORT
     */
    const SHOW_STUDENT_REPORT = 'unplag_show_student_report';
    /**
     * DRAFT_SUBMIT
     */
    const DRAFT_SUBMIT = 'unplag_draft_submit';
    /**
     * CHECK_TYPE
     */
    const CHECK_TYPE = 'check_type';
    /**
     * EXCLUDE_CITATIONS
     */
    const EXCLUDE_CITATIONS = 'exclude_citations';
    /**
     * EXCLUDE_SELF_PLAGIARISM
     */
    const EXCLUDE_SELF_PLAGIARISM = 'exclude_self_plagiarism';
    /**
     * CHECK_ALL_SUBMITTED_ASSIGNMENTS
     */
    const CHECK_ALL_SUBMITTED_ASSIGNMENTS = 'check_all_submitted_assignments';
    /**
     * NO_INDEX_FILES
     */
    const NO_INDEX_FILES = 'no_index_files';
    /**
     * MAX_SUPPORTED_ARCHIVE_FILES_COUNT
     */
    const MAX_SUPPORTED_ARCHIVE_FILES_COUNT = 'max_supported_archive_files_count';

    /**
     * Get assign settings
     *
     * @param int  $cmid
     * @param null $name
     *
     * @param bool $assoc
     *
     * @return \stdClass|array
     */
    public static function get_assign_settings($cmid, $name = null, $assoc = null) {
        global $DB;

        $condition = [
            'cm' => $cmid,
        ];

        if (isset($name)) {
            $condition['name'] = $name;
        }

        $data = $DB->get_records(UNPLAG_CONFIG_TABLE, $condition, '', 'name,value');
        $data = array_map(function($item) {
            return $item->value;
        }, $data);

        if (is_bool($assoc) && $assoc) {
            return $data;
        }

        if (isset($data[$name])) {
            return $data[$name];
        }

        return [];
    }

    /**
     * This function should be used to initialise settings and check if plagiarism is enabled.
     *
     * @param null $key
     *
     * @return array|bool
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_settings($key = null) {
        static $settings;

        if (!empty($settings)) {
            return self::get_settings_item($settings, $key);
        }

        $settings = (array)get_config('plagiarism');

        // Check if enabled.
        if (isset($settings['unplag_use']) && $settings['unplag_use']) {
            // Now check to make sure required settings are set!
            if (empty($settings['unplag_api_secret'])) {
                throw new \coding_exception('UNICHECK API Secret not set!');
            }

            return self::get_settings_item($settings, $key);
        } else {
            return false;
        }
    }

    /**
     * Get item settings
     *
     * @param array $settings
     * @param null  $key
     *
     * @return null
     */
    private static function get_settings_item($settings, $key = null) {
        if (is_null($key)) {
            return $settings;
        }

        $key = 'unplag_' . $key;

        return isset($settings[$key]) ? $settings[$key] : null;
    }
}