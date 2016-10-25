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
 * constants.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

define('UNPLAG_PLAGIN_NAME', 'plagiarism_unplag');
define('UNPLAG_DOMAIN', 'https://un77.mytheverona.com/');
define('UNPLAG_API_URL', UNPLAG_DOMAIN . 'api/v2/');
define('UNPLAG_CALLBACK_URL', '/plagiarism/unplag/ajax.php?action=unplag_callback');

define('UNPLAG_PROJECT_PATH', dirname(__FILE__) . '/');

define('UNPLAG_DEFAULT_FILES_AREA', 'assign_submission');
define('UNPLAG_WORKSHOP_FILES_AREA', 'workshop_submissions');
define('UNPLAG_FORUM_FILES_AREA', 'forum_posts');

/** TABLES **/
define('UNPLAG_FILES_TABLE', 'plagiarism_unplag_files');
define('UNPLAG_CONFIG_TABLE', 'plagiarism_unplag_config');

define('UNPLAG_CHECK_TYPE_WEB', 'web');
define('UNPLAG_CHECK_TYPE_MY_LIBRARY', 'my_library');
define('UNPLAG_CHECK_TYPE_WEB__LIBRARY', 'web_and_my_library');

define('UNPLAG_STATUSCODE_PENDING', 'pending');
define('UNPLAG_STATUSCODE_PROCESSED', 200);
define('UNPLAG_STATUSCODE_ACCEPTED', 202);
define('UNPLAG_STATUSCODE_UNSUPPORTED', 415);
define('UNPLAG_STATUSCODE_INVALID_RESPONSE', 613); // Invalid response received from UNPLAG.

define('UNPLAG_UPLOAD_TIME_LIMIT', 5 * 60); // Time limit for upload file.

define('UNPLAG_WORKSHOP_SETUP_PHASE', 10);
define('UNPLAG_WORKSHOP_SUBMISSION_PHASE', 20);
define('UNPLAG_WORKSHOP_ASSESSMENT_PHASE', 30);
define('UNPLAG_WORKSHOP_GRADING_PHASE', 40);