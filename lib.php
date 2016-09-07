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
 * lib.php - Contains Plagiarism plugin specific functions called by Modules.
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unplag\classes\helpers\unplag_linkarray;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

// Get global class.
global $CFG;

require_once($CFG->dirroot . '/plagiarism/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once(dirname(__FILE__) . '/autoloader.php');
require_once(dirname(__FILE__) . '/locallib.php');

// There is a new UNPLAG API - The Integration Service - we only currently use this to verify the receiver address.
// If we convert the existing calls to send file/get score we should move this to a config setting.
/**
 * Class plagiarism_plugin_unplag
 */
class plagiarism_plugin_unplag extends plagiarism_plugin {
    /**
     * @return array
     */
    public static function default_plagin_options() {
        return array('unplag_use', 'unplag_enable_mod_assign');
    }

    /**
     * Hook to allow plagiarism specific information to be displayed beside a submission.
     *
     * @param $linkarray
     *
     * @return string
     * @internal param array $linkarraycontains all relevant information for the plugin to generate a link.
     *
     */
    public function get_links($linkarray) {
        $file = null;
        $fileobj = null;

        if (!plagiarism_unplag::is_plagin_enabled() || !unplag_settings::get_assign_settings($linkarray['cmid'], 'use_unplag')) {
            // Not allowed access to this content.
            return null;
        }

        $cm = get_coursemodule_from_id('', $linkarray['cmid'], 0, false, MUST_EXIST);

        $output = '';
        $file = unplag_linkarray::get_file_from_linkarray($cm, $linkarray);
        if ($file && plagiarism_unplag::is_support_filearea($file->get_filearea())) {
            $ucore = new unplag_core($linkarray['cmid'], $linkarray['userid']);
            $fileobj = $ucore->get_plagiarism_entity($file)->get_internal_file();
            if (!empty($fileobj) && is_object($fileobj)) {
                $output = unplag_linkarray::get_output_for_linkarray($fileobj, $cm, $linkarray);
            }
        }

        return $output;
    }

    /**
     *  hook to save plagiarism specific settings on a module settings page
     *
     * @param object $data - data from an mform submission.
     */
    public function save_form_elements($data) {
        global $DB;

        if (isset($data->submissiondrafts) && !$data->submissiondrafts) {
            $data->use_unplag = 0;
        }

        if (isset($data->use_unplag)) {
            // First get existing values.
            $existingelements = $DB->get_records_menu(UNPLAG_CONFIG_TABLE, array('cm' => $data->coursemodule), '', 'name, id');
            // Array of possible plagiarism config options.
            foreach (self::config_options() as $element) {
                $newelement = new stdClass();
                $newelement->cm = $data->coursemodule;
                $newelement->name = $element;
                $newelement->value = (isset($data->$element) ? $data->$element : 0);
                if (isset($existingelements[$element])) {
                    $newelement->id = $existingelements[$element];
                    $DB->update_record(UNPLAG_CONFIG_TABLE, $newelement);
                } else {
                    $DB->insert_record(UNPLAG_CONFIG_TABLE, $newelement);
                }
            }
        }
    }

    /**
     * Function which returns an array of all the module instance settings.
     *
     * @return array
     *
     */
    public static function config_options() {
        return array(
                'use_unplag', 'unplag_show_student_score', 'unplag_show_student_report',
                'unplag_draft_submit', 'check_type',
        );
    }

    /**
     * @param $modulename
     * @return bool
     */
    private function is_enabled_module($modulename) {
        $plagiarismsettings = unplag_settings::get_settings();
        $modname = 'unplag_enable_' . $modulename;

        if (!$plagiarismsettings || empty($plagiarismsettings[$modname])) {
            return false;// Return if unplag is not enabled for the module.
        }

        return true;
    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     *
     * @param object $mform - Moodle form
     * @param object $context - current context
     * @param string $modulename
     *
     * @return null
     */
    public function get_form_elements_module($mform, $context, $modulename = "") {
        if ($modulename && !$this->is_enabled_module($modulename)) {
            return null;
        }

        $cmid = optional_param('update', 0, PARAM_INT); // Get cm as $this->_cm is not available here.
        $plagiarismelements = self::config_options();
        if (has_capability('plagiarism/unplag:enable', $context)) {
            require_once(dirname(__FILE__) . '/unplag_form.php');
            $uform = new unplag_defaults_form($mform, $modulename);
            $uform->set_data(unplag_settings::get_assign_settings($cmid, null, true));
            $uform->definition();

            if ($mform->elementExists('submissiondrafts')) {
                // Disable all plagiarism elements if submissiondrafts eg 0.
                foreach ($plagiarismelements as $element) {
                    $mform->disabledIf($element, 'submissiondrafts', 'eq', 0);
                }
            } else {
                if ($mform->elementExists('unplag_draft_submit') && $mform->elementExists('var4')) {
                    $mform->disabledIf('unplag_draft_submit', 'var4', 'eq', 0);
                }
            }
            $this->disable_elements_if_not_use($plagiarismelements, $mform);
        } else { // Add plagiarism settings as hidden vars.
            $this->add_plagiarism_hidden_vars($plagiarismelements, $mform);
        }
    }

    /**
     * @param $plagiarismelements
     * @param $mform
     */
    private function disable_elements_if_not_use($plagiarismelements, $mform) {
        // Disable all plagiarism elements if use_plagiarism eg 0.
        foreach ($plagiarismelements as $element) {
            if ($element <> 'use_unplag') { // Ignore this var.
                $mform->disabledIf($element, 'use_unplag', 'eq', 0);
            }
        }
    }

    /**
     * @param $plagiarismelements
     * @param $mform
     */
    private function add_plagiarism_hidden_vars($plagiarismelements, $mform) {
        foreach ($plagiarismelements as $element) {
            $mform->addElement('hidden', $element);
            $mform->setType('use_unplag', PARAM_INT);
            $mform->setType('unplag_show_student_score', PARAM_INT);
            $mform->setType('unplag_show_student_report', PARAM_INT);
            $mform->setType('unplag_draft_submit', PARAM_INT);
        }
    }

    /**
     * Hook to allow a disclosure to be printed notifying users what will happen with their submission.
     *
     * @param int $cmid - course module id
     *
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT;

        $outputhtml = '';

        $unplaguse = unplag_settings::get_assign_settings($cmid, 'use_unplag');
        $disclosure = unplag_settings::get_settings('student_disclosure');

        if (!empty($disclosure) && $unplaguse) {
            $outputhtml .= $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            $outputhtml .= format_text($disclosure, FORMAT_MOODLE, $formatoptions);
            $outputhtml .= $OUTPUT->box_end();
        }

        return $outputhtml;
    }
}