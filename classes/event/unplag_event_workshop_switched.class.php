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
 * unplag_event_assessable_submited.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\event;

use core\event\base;
use plagiarism_unplag;
use plagiarism_unplag\classes\entities\providers\unplag_file_provider;
use plagiarism_unplag\classes\unplag_adhoc;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_workshop;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once(dirname(__FILE__) . '/../../locallib.php');

/**
 * Class unplag_event_file_submited
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_event_workshop_switched extends unplag_abstract_event {
    /**
     * handle event
     *
     * @param unplag_core $core
     * @param base        $event
     */
    public function handle_event(unplag_core $core, base $event) {

        if (empty($event->other['workshopphase'])) {
            return;
        }

        switch ($event->other['workshopphase']) {
            case UNPLAG_WORKSHOP_SUBMISSION_PHASE:
                $this->submission_phase($core, $event);
                break;
            case UNPLAG_WORKSHOP_ASSESSMENT_PHASE:
                $this->assessment_phase($core, $event);
                break;
        }

    }

    /**
     * handle Submission phase
     *
     * @param unplag_core $core
     * @param base        $event
     */
    public function submission_phase(unplag_core $core, base $event) {
        if (!$event->relateduserid) {
            $core->enable_teamsubmission();
        } else {
            $core->userid = $event->relateduserid;
        }

        $unplagfiles = plagiarism_unplag::get_area_files($event->contextid, UNPLAG_WORKSHOP_FILES_AREA);
        $workshopfiles = unplag_workshop::get_area_files($event->contextid);

        $files = array_merge($unplagfiles, $workshopfiles);

        $ids = [];
        foreach ($files as $file) {
            $plagiarismentity = $core->get_plagiarism_entity($file);
            $internalfile = $plagiarismentity->get_internal_file();
            $ids[] = $internalfile->id;
        }

        unplag_file_provider::delete_by_ids($ids);
    }

    /**
     * handle Assessment phase
     *
     * @param unplag_core $core
     * @param base        $event
     */
    public function assessment_phase(unplag_core $core, base $event) {

        $unplagfiles = plagiarism_unplag::get_area_files($event->contextid, UNPLAG_WORKSHOP_FILES_AREA);
        $workshopfiles = unplag_workshop::get_area_files($event->contextid);

        $files = array_merge($unplagfiles, $workshopfiles);

        if (!empty($files)) {
            foreach ($files as $file) {
                $core->userid = $file->get_userid();
                unplag_adhoc::upload($file, $core);
            }
        }
    }
}