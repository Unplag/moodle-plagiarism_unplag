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
 * unplag_abstract_task.class.php
 *
 * @package     plagiarism_unplag
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\task;

use core\task\adhoc_task;
use core\task\manager;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Abstract unplag_abstract_task
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class unplag_abstract_task extends adhoc_task {
    /**
     * Add new task for execution
     *
     * @param array $data
     *
     * @return bool
     */
    public static function add_task($data) {
        $task = new static();
        $task->set_component(UNPLAG_PLAGIN_NAME);
        $task->set_custom_data($data);

        return manager::queue_adhoc_task($task);
    }

    /**
     * Get modname of executed task
     *
     * @param \object $data
     *
     * @return null|string
     */
    protected function get_modname($data) {

        if (isset($data->modname)) {
            return $data->modname;
        }

        if (!isset($data->cmid)) {
            return null;
        }

        $cm = get_coursemodule_from_id('', $data->cmid);
        if (!$cm) {
            return null;
        }

        return $cm->modname;
    }
}