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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class failed_task
 *
 * @package plagiarism_unplag
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_broken_task extends \core\task\scheduled_task
{
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
        file_put_contents('/var/tmp/moodle_log', 'Test ! 2wef');
//        throw new \Exception('Test Exception');
    }
}
