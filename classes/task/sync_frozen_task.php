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
 * unplag_sync_failed_check_task.class.php
 *
 * @package     plagiarism_unplag
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\task;

use plagiarism_unplag\classes\entities\unplag_archive;
use plagiarism_unplag\classes\helpers\unplag_progress;
use plagiarism_unplag\classes\services\storage\unplag_file_state;
use plagiarism_unplag\classes\unplag_adhoc;
use plagiarism_unplag\classes\unplag_api;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

// Get global class.
global $CFG;

require_once($CFG->dirroot . '/plagiarism/unplag/autoloader.php');
require_once($CFG->dirroot . '/plagiarism/unplag/constants.php');

/**
 * Class failed_task
 *
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_frozen_task extends \core\task\scheduled_task
{
    const CHECK = 'frozen_check';
    const FILE = 'frozen_file';

    /**
     * @return string
     * @throws \coding_exception
     */
    public function get_name()
    {
        return get_string('sync_failed', 'plagiarism_unplag');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute()
    {
        global $DB;

        $offset = 0;
        $limit = 1000;

        $querywhere = "(state <> '" . unplag_file_state::CHECKED . "' OR check_id IS NULL) AND DATE_SUB(NOW(), INTERVAL 1 HOUR) > `timesubmitted` ";

        $frozenFiles = $DB->get_records_select(
            'plagiarism_unplag_files',
            $querywhere,
            null,
            null,
            '*',
            $offset,
            $limit
        );

        $files = [
            self::FILE        => [],
            self::CHECK       => []
        ];

        foreach ($frozenFiles as $id => $file) {
            if (!is_null($file->check_id)) {
                $files[self::CHECK][$file->check_id] = $file;
            } else if (!is_null($file->external_file_id)) {
                $files[self::FILE][$id] = $file;
            }
        }



        if ($files[self::CHECK]) {
            $check_keys = array_keys($files[self::CHECK]);
            $progresses = unplag_api::instance()->get_check_progress($check_keys);
            if (isset($progresses->progress)) {
                foreach ($check_keys as $check_key) {
                    $data = unplag_progress::update_file_progress($check_key, $progresses->progress->$check_key * 100);
                }
            }
        }

        foreach ($files[self::FILE] as $fileForUpdate) {
            unplag_progress::track_upload($fileForUpdate);
        }
    }
}
