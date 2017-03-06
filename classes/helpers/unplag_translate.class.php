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

namespace plagiarism_unplag\classes\helpers;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_translate
 *
 * @package     plagiarism_unplag\classes\helpers
 * @subpackage  plagiarism
 * @namespace   plagiarism_unplag\classes\helpers
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait unplag_translate {
    /**
     * @param      $message
     * @param null $param
     *
     * @return string
     */
    public static function trans($message, $param = null) {
        return get_string($message, 'plagiarism_unplag', $param);
    }

    /**
     * @param $error
     *
     * @return string
     */
    private static function api_trans($error) {
        static $translates;

        if (empty($translates)) {
            $lang = current_language();
            $path = UNPLAG_PROJECT_PATH . "lang/$lang/api_translates.json";
            if (file_exists($path)) {
                $translates = json_decode(file_get_contents($path));
            }
        }

        $error = isset($error['extra_params']) ? self::trans($error['extra_params']) : $error['message'];

        return isset($translates->{$error}) ? $translates->{$error} : $error;
    }
}