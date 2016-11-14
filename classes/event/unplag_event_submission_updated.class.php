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
 * unplag_event_file_submited.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\event;

use core\event\base;
use plagiarism_unplag\classes\unplag_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_event_submission_updated
 *
 * @package plagiarism_unplag\classes\event
 * @namespace plagiarism_unplag\classes\event
 *
 */
class unplag_event_submission_updated extends unplag_abstract_event {

    const DRAFT_STATUS = 'draft';

    /**
     * @param unplag_core $unplagcore
     * @param base $event
     * @return bool
     */
    public function handle_event(unplag_core $unplagcore, base $event) {

        global $DB;
        if (!isset($event->other['newstatus'])) {
            return false;
        }
        $newstatus = $event->other['newstatus'];
        $unplagcore->userid = $event->relateduserid;
        if ($newstatus == self::DRAFT_STATUS) {
            $unplagfiles = \plagiarism_unplag::get_area_files($event->contextid, UNPLAG_DEFAULT_FILES_AREA, $event->objectid);
            $assignfiles = get_file_storage()->get_area_files($event->contextid,
                    'assignsubmission_file', 'submission_files', $event->objectid, null, false
            );

            $files = array_merge($unplagfiles, $assignfiles);

            $ids = array();
            foreach ($files as $file) {
                $plagiarismentity = $unplagcore->get_plagiarism_entity($file);
                $internalfile = $plagiarismentity->get_internal_file();
                $ids[] = $internalfile->id;
            }

            $allrecordssql = implode(',', $ids);
            $DB->delete_records_select(UNPLAG_FILES_TABLE, "id IN ($allrecordssql) OR parent_id IN ($allrecordssql)");
        }

        return true;
    }
}