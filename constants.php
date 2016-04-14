<?php

define('UNPLAG_PLAGIN_NAME', 'plagiarism_unplag');
define('UNPLAG_DOMAIN', 'https://unplag.com/');
define('UNPLAG_API_URL', 'http://un16.mytheverona.com/api/v2/');

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
