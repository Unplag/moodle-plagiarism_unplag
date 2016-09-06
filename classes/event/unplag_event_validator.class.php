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

require_once(dirname(__FILE__) . '/../constants.php');

/**
 * Class unplag_event_validator
 *
 * @package plagiarism_unplag\classes\event
 * @subpackage  plagiarism
 * @namespace plagiarism_unplag\classes\event
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_event_validator {

    /**
     * @param base $event
     *
     * @throws \Exception
     */
    public static function validate_event(base $event) {
        global $DB;

        $cmid = $event->contextinstanceid;

        $plagiarismvalues = $DB->get_records_menu(UNPLAG_CONFIG_TABLE, array('cm' => $cmid), '', 'name, value');
        if (empty($plagiarismvalues['use_unplag'])) {
            // Unplag not in use for this cm - return.
            throw new \Exception('Unplag not in use for this cm');
        }

        // Check if the module associated with this event still exists.
        if (!$DB->record_exists('course_modules', array('id' => $cmid))) {
            throw new \Exception('Module not associated with this event');
        }
    }
}