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

use plagiarism_unplag\classes\unplag_notification;
use plagiarism_unplag\classes\unplag_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_check_helper
 *
 * @package plagiarism_unplag\classes\helpers
 * @subpackage  plagiarism
 * @namespace plagiarism_unplag\classes\helpers
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_check_helper {
    /**
     * @param \stdClass $record
     * @param \stdClass $check
     * @param int $progress
     */
    public static function check_complete(\stdClass &$record, \stdClass $check, $progress = 100) {
        global $DB;

        if ($progress == 100) {
            $record->statuscode = UNPLAG_STATUSCODE_PROCESSED;
        }

        $record->similarityscore = $check->report->similarity;
        $record->reporturl = $check->report->view_url;
        $record->reportediturl = $check->report->view_edit_url;
        $record->progress = $progress;

        $updated = $DB->update_record(UNPLAG_FILES_TABLE, $record);

        $emailstudents = unplag_settings::get_assign_settings($record->cm, 'unplag_studentemail');
        if ($updated && !empty($emailstudents)) {
            unplag_notification::send_student_email_notification($record);
        }

        if ($updated && $record->parent_id !== null) {
            $parentrecord = $DB->get_record(UNPLAG_FILES_TABLE, array('id' => $record->parent_id));
            $childs = $DB->get_records(UNPLAG_FILES_TABLE, array('parent_id' => $parentrecord->id, 'errorresponse' => null));
            $similarity = 0;
            $cpf = null;
            $parentprogress = 0;
            foreach ($childs as $child) {
                $parentprogress += $child->progress;
                $similarity += $child->similarityscore;
            }

            $parentprogress = round($parentprogress / count($childs), 2, PHP_ROUND_HALF_DOWN);
            $reporturl = new \moodle_url('/plagiarism/unplag/reports.php', array(
                    'cmid' => $parentrecord->cm,
                    'pf' => $parentrecord->id
            ));

            $parentcheck = array(
                    'report' => array(
                            'similarity' => round($similarity / count($childs), 2, PHP_ROUND_HALF_DOWN),
                            'view_url' => (string) $reporturl->out_as_local_url(),
                            'view_edit_url' => (string) $reporturl->out_as_local_url()
                    )
            );

            $parentcheck = json_decode(json_encode($parentcheck));
            self::check_complete($parentrecord, $parentcheck, $parentprogress);
        }
    }
}