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
        global $DB;

        $submission = unplag_assign::get_user_submission_by_cmid($event->contextinstanceid);
        /* Only for team submission */
        $isgroup = (bool)$DB->get_record('assign', array('id' => $submission->assignment), 'teamsubmission')->teamsubmission;
        if (!$isgroup || $submission->status == unplag_event_submission_updated::DRAFT_STATUS) {
            return;
        }

        $assignfiles = unplag_assign::get_submission_files($event->contextid);
        foreach ($assignfiles as $assignfile) {
            if ($assignfile->get_userid() != $event->userid) {
                continue;
            }

            $plagiarismentity = $unplagcore->get_plagiarism_entity($assignfile);
            $internalfile = $plagiarismentity->get_internal_file();
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
}