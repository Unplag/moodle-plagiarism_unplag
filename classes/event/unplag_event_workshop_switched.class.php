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
use plagiarism_unplag\classes\unplag_adhoc;
use plagiarism_unplag\classes\unplag_core;

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

        if (!empty($event->other['workshopphase'])
            && $event->other['workshopphase'] == UNPLAG_WORKSHOP_ASSESSMENT_PHASE
        ) { // Assessment phase.
            $this->ucore = $core;

            $unplagfiles = plagiarism_unplag::get_area_files($event->contextid, UNPLAG_WORKSHOP_FILES_AREA);
            $assignfiles = get_file_storage()->get_area_files($event->contextid,
                'mod_workshop', 'submission_attachment', false, null, false
            );

            $files = array_merge($unplagfiles, $assignfiles);

            if (!empty($files)) {
                foreach ($files as $file) {
                    $this->handle_file_plagiarism($file);
                }
            }
        }
    }

    /**
     * handle_file_plagiarism
     *
     * @param \stored_file $file
     *
     * @return bool
     */
    private function handle_file_plagiarism($file) {
        $this->ucore->userid = $file->get_userid();

        return unplag_adhoc::upload($file, $this->ucore);
    }
}