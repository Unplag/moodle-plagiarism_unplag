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
 * unplag_exception.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\exception;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class UnplagException
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 *
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_exception extends \Exception {
    /**
     * ARCHIVE_IS_EMPTY
     */
    const ARCHIVE_IS_EMPTY = 'Archive is empty or contains document(s) with no text';
    /**
     * ARCHIVE_CANT_BE_OPEN
     */
    const ARCHIVE_CANT_BE_OPEN = 'Can\'t open zip archive';
    /**
     * UNSUPPORTED_MIMETYPE
     */
    const UNSUPPORTED_MIMETYPE = 'Unsupported mimetype';
    /**
     * FILE_NOT_FOUND
     */
    const FILE_NOT_FOUND = 'File not found';
}