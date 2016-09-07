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

/**
 * Class unplag_settings
 *
 * @package plagiarism_unplag\classes
 * @subpackage  plagiarism
 * @namespace plagiarism_unplag\classes
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_settings {
    /**
     * @param      $cmid
     * @param null $name
     *
     * @param      $assoc
     *
     * @return \stdClass|array
     */
    public static function get_assign_settings($cmid, $name = null, $assoc = null) {
        global $DB;

        $condition = array(
                'cm' => $cmid,
        );

        if (isset($name)) {
            $condition['name'] = $name;
        }

        $data = $DB->get_records(UNPLAG_CONFIG_TABLE, $condition, '', 'name,value');
        $data = array_map(function($item) {
            return $item->value;
        }, $data);

        return (is_bool($assoc) && $assoc) ? $data : $data[$name];
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

        $settings = (array) get_config('plagiarism');

        // Check if enabled.
        if (isset($settings['unplag_use']) && $settings['unplag_use']) {
            // Now check to make sure required settings are set!
            if (empty($settings['unplag_api_secret'])) {
                throw new \coding_exception('UNPLAG API Secret not set!');
            }

            return self::get_settings_item($settings, $key);
        } else {
            return false;
        }
    }

    /**
     * @param      $settings
     * @param null $key
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