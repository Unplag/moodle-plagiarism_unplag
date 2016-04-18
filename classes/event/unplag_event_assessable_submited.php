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

namespace plagiarism_unplag\classes\event;

use core\event\base;
use plagiarism_unplag;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;

require_once(dirname(__FILE__) . '/unplag_abstract_event.php');
require_once(dirname(__FILE__) . '/../../locallib.php');

/**
 * Class unplag_event_file_submited
 * @package plagiarism_unplag\classes\event
 */
class unplag_event_assessable_submited extends unplag_abstract_event {
    /** @var self */
    protected static $instance;
    /** @var unplag_core */
    private $unplagcore;

    /**
     * @param unplag_core $unplagcore
     * @param base        $event
     */
    public function handle_event(unplag_core $unplagcore, base $event) {

        $this->unplagcore = $unplagcore;

        $unplagfiles = plagiarism_unplag::get_area_files($event->contextinstanceid);
        $assignfiles = get_file_storage()->get_area_files($event->contextid,
            'assignsubmission_file', 'submission_files', false, null, false
        );
        $files = array_merge($unplagfiles, $assignfiles);
        if ($files) {
            foreach ($files as $file) {
                $this->handle_file_plagiarism($file);
            }
        }
    }

    /**
     * @param $file
     *
     * @throws \moodle_exception
     */
    private function handle_file_plagiarism($file) {
        $plagiarismentity = $this->unplagcore->get_plagiarism_entity($file);
        $internalfile = $plagiarismentity->get_internal_file();
        if ($internalfile->check_id) {
            print_error('File with uuid' . $file->identifier . ' already sent to Unplag');
        } else {
            unplag_api::instance()->run_check($internalfile);
            mtrace('file ' . $file->identifier . 'send to Unplag');
        }
    }
}