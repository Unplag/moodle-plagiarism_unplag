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
 * unplag_archive.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\entities;

use core\event\base;
use plagiarism_unplag\classes\event\unplag_event_assessable_submited;
use plagiarism_unplag\classes\event\unplag_event_file_submited;
use plagiarism_unplag\classes\event\unplag_event_group_submition;
use plagiarism_unplag\classes\event\unplag_event_onlinetext_submited;
use plagiarism_unplag\classes\event\unplag_event_submission_updated;
use plagiarism_unplag\classes\event\unplag_event_workshop_switched;
use plagiarism_unplag\classes\unplag_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_archive
 *
 * @package plagiarism_unplag\classes\entities
 * @namespace plagiarism_unplag\classes\entities
 *
 */
class unplag_event {

    /**
     * @param base $event
     */
    public function process(base $event) {
        $unplagcore = new unplag_core($event->get_context()->instanceid, $event->userid);

        if (self::is_upload_event($event)) {
            switch ($event->component) {
                case 'assignsubmission_onlinetext':
                    unplag_event_onlinetext_submited::instance()->handle_event($unplagcore, $event);
                    break;
                case 'assignsubmission_file':
                    unplag_event_file_submited::instance()->handle_event($unplagcore, $event);
                    break;
                case 'mod_workshop':
                    $unplagcore->create_file_from_content($event);
                    break;
                case 'mod_forum':
                    unplag_event_onlinetext_submited::instance()->handle_event($unplagcore, $event);
                    unplag_event_file_submited::instance()->handle_event($unplagcore, $event);
                    break;
            }
        } else if (self::is_assign_submitted($event)) {
            unplag_event_assessable_submited::instance()->handle_event($unplagcore, $event);
        } else if (self::is_workshop_swiched($event)) {
            unplag_event_workshop_switched::instance()->handle_event($unplagcore, $event);
        } else {
            switch ($event->eventname) {
                case '\mod_assign\event\submission_status_updated':
                    unplag_event_submission_updated::instance()->handle_event($unplagcore, $event);
                    break;
                case '\mod_assign\event\submission_status_viewed':
                    unplag_event_group_submition::instance()->handle_event($unplagcore, $event);
                    break;
            }
        }
    }

    /**
     * @param base $event
     *
     * @return bool
     */
    private static function is_upload_event(base $event) {
        $eventdata = $event->get_data();
        return in_array($eventdata['eventname'], array(
                '\assignsubmission_file\event\submission_updated',
                '\assignsubmission_file\event\assessable_uploaded',
                '\assignsubmission_onlinetext\event\assessable_uploaded',
                '\mod_forum\event\assessable_uploaded',
                '\mod_workshop\event\assessable_uploaded'
        ));
    }

    /**
     * @param base $event
     *
     * @return bool
     */
    private static function is_assign_submitted(base $event) {
        return $event->target == 'assessable' && $event->action == 'submitted';
    }

    /**
     * @param base $event
     *
     * @return bool
     */
    private static function is_workshop_swiched(base $event) {
        return $event->target == 'phase' && $event->action == 'switched' && $event->component == 'mod_workshop';
    }
}
