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
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\event;

use core\event\base;
use plagiarism_unplag;
use plagiarism_unplag\classes\entities\unplag_archive;
use plagiarism_unplag\classes\unplag_assign;
use plagiarism_unplag\classes\unplag_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once(dirname(__FILE__) . '/../../locallib.php');

/**
 * Class unplag_event_file_submited
 *
 * @package plagiarism_unplag\classes\event
 */
class unplag_event_assessable_submited extends unplag_abstract_event {
    /** @var self */
    protected static $instance;

    /**
     * @param unplag_core $unplagcore
     * @param base        $event
     */
    public function handle_event(unplag_core $unplagcore, base $event) {

        $this->unplagcore = $unplagcore;

        $submission = unplag_assign::get_user_submission_by_cmid($event->contextinstanceid);
        $submissionid = (!empty($submission->id) ? $submission->id : false);

        $unplagfiles = plagiarism_unplag::get_area_files($event->contextid, UNPLAG_DEFAULT_FILES_AREA, $submissionid);
        $assignfiles = unplag_assign::get_area_files($event->contextid, $submissionid);

        $files = array_merge($unplagfiles, $assignfiles);
        if (!empty($files)) {
            foreach ($files as $file) {
                $this->handle_file_plagiarism($file);
            }
        }
    }

    /**
     * @param \stored_file $file
     *
     * @return bool
     */
    private function handle_file_plagiarism(\stored_file $file) {
        if (\plagiarism_unplag::is_archive($file)) {
            $unplagarchive = new unplag_archive($file, $this->unplagcore);
            $unplagarchive->run_checks();

            return true;
        }

        $plagiarismentity = $this->unplagcore->get_plagiarism_entity($file);
        return $this->upload_and_run_check($plagiarismentity);
    }
}