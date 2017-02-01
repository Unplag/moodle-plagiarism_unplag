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
 * unplag_event_group_submition.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\event;

use core\event\base;
use plagiarism_unplag\classes\entities\unplag_archive;
use plagiarism_unplag\classes\unplag_assign;
use plagiarism_unplag\classes\unplag_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_event_group_submition
 * @package plagiarism_unplag\classes\event
 */
class unplag_event_group_submition extends unplag_abstract_event {
    /**
     * @param unplag_core $unplagcore
     * @param base        $event
     */
    public function handle_event(unplag_core $unplagcore, base $event) {

        $submission = unplag_assign::get_user_submission_by_cmid($event->contextinstanceid);
        if (!$submission) {
            return;
        }

        $assign = unplag_assign::get($submission->assignment);

        /* Only for team submission */
        if ($submission->status == unplag_event_submission_updated::DRAFT_STATUS || !(bool) $assign->teamsubmission) {
            return;
        }

        /* All users of group must confirm submission */
        if ((bool) $assign->requireallteammemberssubmit && !$this->all_users_confirm_submition($assign)) {
            return;
        }

        $unplagcore->enable_teamsubmission();

        $assignfiles = unplag_assign::get_area_files($event->contextid);
        foreach ($assignfiles as $assignfile) {
            $plagiarismentity = $unplagcore->get_plagiarism_entity($assignfile);
            $internalfile = $plagiarismentity->get_internal_file();

            if ($internalfile->statuscode == UNPLAG_STATUSCODE_PROCESSED) {
                continue;
            }

            if (\plagiarism_unplag::is_archive($assignfile)) {
                $unplagarchive = new unplag_archive($assignfile, $unplagcore);
                $unplagarchive->run_checks();

                continue;
            }

            if ($internalfile->check_id) {
                continue;
            }

            if ($internalfile->external_file_id == null) {
                $plagiarismentity->upload_file_on_unplag_server();
                $this->add_after_handle_task($plagiarismentity);
            }
        }

        $this->after_handle_event();
    }

    /**
     * @param $assign
     *
     * @return bool
     */
    private function all_users_confirm_submition($assign) {
        global $USER;

        list($course, $cm) = get_course_and_cm_from_instance($assign, 'assign');

        $assign = new \assign(\context_module::instance($cm->id), $cm, $course);

        $groupid = $assign->get_submission_group($USER->id)->id;
        $notsubmitted = $assign->get_submission_group_members_who_have_not_submitted($groupid, true);

        return count($notsubmitted) == 0;
    }
}