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
 * filesize_checker.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\services\storage;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class filesize_checker
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filesize_checker {
    /**
     * MAX_FILESIZE
     */
    const MAX_FILESIZE = '70M';

    /**
     * Check if file is too large
     *
     * @param \stored_file $file
     * @return bool
     */
    public static function file_is_to_large(\stored_file $file) {
        if ($file->get_filesize() > get_real_size(self::MAX_FILESIZE)) {
            return true;
        }

        return false;
    }

    /**
     * Check if filesize is too large
     *
     * @param int $filesize In bytes
     * @return bool
     */
    public static function is_too_large($filesize) {
        if ($filesize > get_real_size(self::MAX_FILESIZE)) {
            return true;
        }

        return false;
    }
}