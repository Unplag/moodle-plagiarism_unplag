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

define('UNPLAG_PLAGIN_NAME', 'plagiarism_unplag');
define('UNPLAG_DOMAIN', 'http://un16.mytheverona.com/');
define('UNPLAG_API_URL', UNPLAG_DOMAIN . 'api/v2/');
define('UNPLAG_CALLBACK_URL', '/plagiarism/unplag/ajax.php?action=unplag_callback');

define('UNPLAG_PROJECT_PATH', dirname(__FILE__) . '/');

define('UNPLAG_FILES_AREA', 'submission_files');

/** TABLES **/
define('UNPLAG_FILES_TABLE', 'plagiarism_unplag_files');
define('UNPLAG_CONFIG_TABLE', 'plagiarism_unplag_config');

define('UNPLAG_MAX_SUBMISSION_ATTEMPTS', 6); // Maximum number of times to try and send a submission to UNPLAG.
define('UNPLAG_MAX_SUBMISSION_DELAY', 60); // Maximum time to wait between submissions (defined in minutes).
define('UNPLAG_SUBMISSION_DELAY', 15); // Initial delay, doubled each time a check is made until the max_submission_delay is met.
define('UNPLAG_MAX_STATUS_ATTEMPTS', 10); // Maximum number of times to try and obtain the status of a submission.
define('UNPLAG_MAX_STATUS_DELAY', 1440); // Maximum time to wait between checks (defined in minutes).
define('UNPLAG_STATUS_DELAY', 30); // Initial delay, doubled each time a check is made until the max_status_delay is met.

define('STATUSCODE_PENDING', 'pending');

define('UNPLAG_STATUSCODE_PROCESSED', 200);
define('UNPLAG_STATUSCODE_ACCEPTED', 202);
define('UNPLAG_STATUSCODE_UNSUPPORTED', 415);
define('UNPLAG_STATUSCODE_INVALID_RESPONSE', 613); // Invalid response received from UNPLAG.

define('PLAGIARISM_UNPLAG_DRAFTSUBMIT_IMMEDIATE', 0);
define('PLAGIARISM_UNPLAG_DRAFTSUBMIT_FINAL', 1);
