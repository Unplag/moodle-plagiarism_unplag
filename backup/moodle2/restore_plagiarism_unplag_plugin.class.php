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
 * restore_plagiarism_unplag_plugin.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class restore_plagiarism_unplag_plugin
 */
class restore_plagiarism_unplag_plugin extends restore_plagiarism_plugin {
    /**
     * @param $data
     */
    public function process_unplagconfig($data) {
        $data = (object) $data;

        set_config($this->task->get_courseid(), $data->value, $data->plugin);
    }

    /**
     * @param $data
     */
    public function process_unplagconfigmod($data) {
        global $DB;

        $data = (object) $data;
        $data->cm = $this->task->get_moduleid();

        $DB->insert_record(UNPLAG_CONFIG_TABLE, $data);
    }

    /**
     * @param $data
     */
    public function process_unplagfiles($data) {
        global $DB;

        $data = (object) $data;
        $data->cm = $this->task->get_moduleid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('plagiarism_unplag_files', $data);
    }

    /**
     * Returns the paths to be handled by the plugin at question level.
     */
    protected function define_course_plugin_structure() {
        $paths = array();

        // Add own format stuff.
        $elename = 'unplagconfig';
        $elepath = $this->get_pathfor('unplag_configs/unplag_config'); // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths.
    }

    /**
     * Returns the paths to be handled by the plugin at module level.
     */
    protected function define_module_plugin_structure() {
        $paths = array();

        // Add own format stuff.
        $elename = 'unplagconfigmod';
        $elepath = $this->get_pathfor('unplag_configs/unplag_config'); // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = 'unplagfiles';
        $elepath = $this->get_pathfor('/unplag_files/unplag_file'); // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths.

    }
}