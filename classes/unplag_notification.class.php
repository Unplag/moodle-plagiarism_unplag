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
 * unplag_notification.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes;

use plagiarism_unplag;

/**
 * Class unplag_notification
 *
 * @package plagiarism_unplag\classes
 */
class unplag_notification {
    /** @var string */
    private static $notifyerror = 'notifyproblem';
    /** @var string */
    private static $notifysuccess = 'notifysuccess';
    /** @var string */
    private static $notifymessage = 'notifymessage';

    /**
     * @param      $message
     * @param      $translate
     */
    public static function error($message, $translate) {
        echo self::notify($message, self::$notifyerror, $translate);
    }

    /**
     * @param      $message
     * @param      $level
     * @param      $translate
     *
     * @return string
     */
    private static function notify($message, $level, $translate) {
        global $OUTPUT;

        if (empty($message)) {
            return '';
        }

        $message = (is_bool($translate) && $translate) ? plagiarism_unplag::trans($message) : $message;

        return $OUTPUT->notification($message, $level);
    }

    /**
     * @param      $message
     * @param      $translate
     */
    public static function success($message, $translate) {
        echo self::notify($message, self::$notifysuccess, $translate);
    }

    /**
     * @param      $message
     * @param      $translate
     */
    public static function message($message, $translate) {
        echo self::notify($message, self::$notifymessage, $translate);
    }
}