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
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}
require_once($CFG->dirroot.'/lib/formslib.php');

class unplag_setup_form extends moodleform {

    // Define the form.
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        //$mform->addElement('html', get_string('unplagexplain', 'plagiarism_unplag'));
        $mform->addElement('checkbox', 'unplag_use', get_string('useunplag', 'plagiarism_unplag'));

        $mform->addElement('text', 'unplag_client_id', get_string('unplag_client_id', 'plagiarism_unplag'));
        $mform->addHelpButton('unplag_client_id', 'unplag_client_id', 'plagiarism_unplag');
        $mform->addRule('unplag_client_id', null, 'required', null, 'client');
        $mform->setType('unplag_client_id', PARAM_TEXT);

        $mform->addElement('text', 'unplag_api_secret', get_string('unplag_api_secret', 'plagiarism_unplag'));
        $mform->addHelpButton('unplag_api_secret', 'unplag_api_secret', 'plagiarism_unplag');
        $mform->addRule('unplag_api_secret', null, 'required', null, 'client');
        $mform->setType('unplag_api_secret', PARAM_TEXT);

        $mform->addElement('text', 'unplag_lang', get_string('unplag_lang', 'plagiarism_unplag'));
        $mform->addHelpButton('unplag_lang', 'unplag_lang', 'plagiarism_unplag');
        $mform->addRule('unplag_lang', null, 'required', null, 'client');
        $mform->setDefault('unplag_lang', 'en-US');
        $mform->setType('unplag_lang', PARAM_TEXT);

        $mform->addElement('textarea', 'unplag_student_disclosure', get_string('studentdisclosure', 'plagiarism_unplag'),
                           'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('unplag_student_disclosure', 'studentdisclosure', 'plagiarism_unplag');
        $mform->setDefault('unplag_student_disclosure', get_string('studentdisclosuredefault', 'plagiarism_unplag'));
        $mform->setType('unplag_student_disclosure', PARAM_TEXT);

        $mods = core_component::get_plugin_list('mod');
        foreach ($mods as $mod => $modname) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                $modstring = 'unplag_enable_mod_' . $mod;
                $mform->addElement('checkbox', $modstring, get_string('unplag_enableplugin', 'plagiarism_unplag', $mod));
            }
        }

        $this->add_action_buttons(true);
    }
}

class unplag_defaults_form extends moodleform {

    // Define the form.
    function definition () {
        $mform =& $this->_form;
        plagiarism_plugin_unplag::unplag_get_form_elements($mform);
        $this->add_action_buttons(true);
    }
}
