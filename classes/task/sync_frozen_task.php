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
 * sync_frozen_task.php
 *
 * @package     plagiarism_unplag
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\task;

use plagiarism_unplag\classes\entities\providers\unplag_file_provider;
use plagiarism_unplag\classes\services\api\unplag_check_api;
use plagiarism_unplag\classes\services\api\unplag_file_api;
use plagiarism_unplag\classes\services\storage\unplag_file_state;
use plagiarism_unplag\classes\unplag_adhoc;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

// Get global class.
global $CFG;

require_once($CFG->dirroot . '/plagiarism/unplag/autoloader.php');
require_once($CFG->dirroot . '/plagiarism/unplag/constants.php');
require_once($CFG->libdir  . '/filelib.php');

/**
 * Class failed_task
 *
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_frozen_task extends \core\task\scheduled_task
{
    /**
     * Identify frozen check
     */
    const CHECK = 'frozen_check';

    /**
     * Identify frozen file
     */
    const FILE  = 'frozen_file';

    /**
     * Get name for this task
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('sync_failed', 'plagiarism_unplag');
    }

    /**
     * Do the job.
     *
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {

        $files = [
            self::FILE        => [],
            self::CHECK       => []
        ];

        $frozenfiles = unplag_file_provider::get_frozen_files();

        if ($frozenfiles) {
            foreach ($frozenfiles as $id => $file) {
                if (!is_null($file->check_id)) {
                    $files[self::CHECK][$file->check_id] = $file;
                } else if (!is_null($file->external_file_uuid)) {
                    $files[self::FILE][$file->id] = $file;
                }
            }

            if ($files[self::CHECK]) {
                $checkservice = new unplag_check_api();
                $cheklist = $checkservice->get_finished_check_by_ids(array_keys($files[self::CHECK]));
                if ($cheklist) {
                    $this->fix_check($cheklist, $files[self::CHECK]);
                }
            }

            if ($files[self::FILE]) {
                $checkservice = new unplag_file_api();
                $filelist = $checkservice->get_uploaded_file_by_dbfiles($files[self::FILE]);
                if ($filelist) {
                    $this->fix_file($filelist, $files[self::FILE]);
                }
            }
        }

        $this->fix_archive();
    }

    /**
     * Fix frozen check
     *
     * @param array $externalcheklist
     * @param array $dbchecklist
     */
    protected function fix_check($externalcheklist, $dbchecklist) {
        foreach ($externalcheklist as $externalcheck) {
            if (isset($dbchecklist[$externalcheck->check->id])) {
                unplag_file_provider::update_frozen_check(
                    $dbchecklist[$externalcheck->check->id],
                    $externalcheck->check
                );
            }
        }
    }

    /**
     * Fix frozen file
     *
     * @param array $externalfiles
     * @param array $dbfiles
     */
    protected function fix_file($externalfiles, $dbfiles) {
        if ($externalfiles[unplag_file_api::TO_UPDATE]) {
            foreach ($externalfiles[unplag_file_api::TO_UPDATE] as $key => $check) {
                unplag_file_provider::update_frozen_check(
                    $dbfiles[$key],
                    $check
                );
            }
        }

        if ($externalfiles[unplag_file_api::TO_CREATE]) {
            foreach ($externalfiles[unplag_file_api::TO_CREATE] as $file) {
                unplag_adhoc::check($file);
            }
        }

        if ($externalfiles[unplag_file_api::TO_ERROR]) {
            foreach ($externalfiles[unplag_file_api::TO_ERROR] as $file) {
                unplag_file_provider::to_error_state(
                    $file,
                    get_string('upload_error', 'plagiarism_unplag')
                );
            }
        }
    }

    /**
     * Fix frozen archive
     */
    protected function fix_archive() {
        $fronzenarchive = unplag_file_provider::get_frozen_archive();

        foreach ($fronzenarchive as $archive) {
            $trackedfiles = unplag_file_provider::get_file_list_by_parent_id($archive->id);
            if (count($trackedfiles)) {
                $checkedcount = 0;
                $haserrorcount = 0;
                $similarity = 0;
                foreach ($trackedfiles as $file) {
                    if ($file->state == unplag_file_state::CHECKED) {
                        $checkedcount++;
                        $similarity += $file->similarityscore;
                    } else if ($file->state == unplag_file_state::HAS_ERROR) {
                        $haserrorcount++;
                    }
                }
                if (($checkedcount == count($trackedfiles))
                    OR ($checkedcount != 0 AND ($checkedcount + $haserrorcount) == count($trackedfiles))) {
                    $reporturl = new \moodle_url('/plagiarism/unplag/reports.php', [
                        'cmid' => $archive->cm,
                        'pf'   => $archive->id,
                    ]);
                    $archivesimilarity = round($similarity / $checkedcount, 2, PHP_ROUND_HALF_DOWN);
                    $archive->progress = 100;
                    $archive->reporturl = (string)$reporturl->out_as_local_url();
                    $archive->reportediturl = (string)$reporturl->out_as_local_url();
                    $archive->similarityscore = $archivesimilarity;
                    $archive->state = unplag_file_state::CHECKED;
                    unplag_file_provider::save($archive);
                } else if ($haserrorcount == count($trackedfiles)) {
                    $archive->state = unplag_file_state::HAS_ERROR;
                    unplag_file_provider::save($archive);
                }
            }
        }
    }
}
