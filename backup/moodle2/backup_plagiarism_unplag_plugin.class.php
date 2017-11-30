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
 * backup_plagiarism_unplag_plugin.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class backup_plagiarism_unplag_plugin
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_plagiarism_unplag_plugin extends backup_plagiarism_plugin {
    /**
     * define_module_plugin_structure
     *
     * @return mixed
     */
    public function define_module_plugin_structure() {
        // Define the virtual plugin element without conditions as the global class checks already.
        $plugin = $this->get_plugin_element();

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        $unplagconfigs = new backup_nested_element('unplag_configs');
        $unplagconfig = new backup_nested_element('unplag_config', ['id'], ['name', 'value']);
        $pluginwrapper->add_child($unplagconfigs);
        $unplagconfigs->add_child($unplagconfig);
        $unplagconfig->set_source_table(UNPLAG_CONFIG_TABLE, ['cm' => backup::VAR_PARENTID]);

        // Now information about files to module.
        $unplagfiles = new backup_nested_element('unplag_files');
        $unplagfile = new backup_nested_element('unplag_file', ['id'], [
            'userid', 'identifier', 'filename', 'progress', 'reporturl', 'optout', 'statuscode',
            'similarityscore', 'errorresponse', 'timesubmitted',
        ]);

        $pluginwrapper->add_child($unplagfiles);
        $unplagfiles->add_child($unplagfile);

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');
        if ($userinfo) {
            $unplagfile->set_source_table('plagiarism_unplag_files', ['cm' => backup::VAR_PARENTID]);
        }

        return $plugin;
    }

    /**
     * define_course_plugin_structure
     *
     * @return mixed
     */
    public function define_course_plugin_structure() {
        // Define the virtual plugin element without conditions as the global class checks already.
        $plugin = $this->get_plugin_element();

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);
        // Save id from UNICHECK course.
        $unplagconfigs = new backup_nested_element('unplag_configs');
        $unplagconfig = new backup_nested_element('unplag_config', ['id'], ['plugin', 'name', 'value']);
        $pluginwrapper->add_child($unplagconfigs);
        $unplagconfigs->add_child($unplagconfig);
        $unplagconfig->set_source_table('config_plugins', [
            'name' => backup::VAR_PARENTID, 'plugin' => backup_helper::is_sqlparam('plagiarism_unplag_course'),
        ]);

        return $plugin;
    }
}