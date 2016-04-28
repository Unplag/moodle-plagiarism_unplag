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
 * unplag_form.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class unplag_setup_form
 */
class unplag_setup_form extends moodleform {
    // Define the form.
    /**
     * @throws coding_exception
     */
    public function definition() {
        $mform =& $this->_form;
        $mform->addElement('checkbox', 'unplag_use', plagiarism_unplag::trans('useunplag'));

        $mform->addElement('text', 'unplag_client_id', plagiarism_unplag::trans('unplag_client_id'));
        $mform->addHelpButton('unplag_client_id', 'unplag_client_id', 'plagiarism_unplag');
        $mform->addRule('unplag_client_id', null, 'required', null, 'client');
        $mform->setType('unplag_client_id', PARAM_TEXT);

        $mform->addElement('text', 'unplag_api_secret', plagiarism_unplag::trans('unplag_api_secret'));
        $mform->addHelpButton('unplag_api_secret', 'unplag_api_secret', 'plagiarism_unplag');
        $mform->addRule('unplag_api_secret', null, 'required', null, 'client');
        $mform->setType('unplag_api_secret', PARAM_TEXT);

        $mform->addElement('text', 'unplag_lang', plagiarism_unplag::trans('unplag_lang'));
        $mform->addHelpButton('unplag_lang', 'unplag_lang', 'plagiarism_unplag');
        $mform->addRule('unplag_lang', null, 'required', null, 'client');
        $mform->setDefault('unplag_lang', 'en-US');
        $mform->setType('unplag_lang', PARAM_TEXT);

        $mform->addElement('textarea', 'unplag_student_disclosure', plagiarism_unplag::trans('studentdisclosure'),
            'wrap="virtual" rows="6" cols="100"');
        $mform->addHelpButton('unplag_student_disclosure', 'studentdisclosure', 'plagiarism_unplag');
        $mform->setDefault('unplag_student_disclosure', plagiarism_unplag::trans('studentdisclosuredefault'));
        $mform->setType('unplag_student_disclosure', PARAM_TEXT);

        $mods = core_component::get_plugin_list('mod');
        foreach ($mods as $mod => $modname) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM) && plagiarism_unplag::is_support_mod($mod)) {
                $modstring = 'unplag_enable_mod_' . $mod;
                $mform->addElement('checkbox', $modstring, plagiarism_unplag::trans('unplag_enableplugin', $mod));
            }
        }

        $this->add_action_buttons(true);
    }
}

/**
 * Class unplag_defaults_form
 */
class unplag_defaults_form extends moodleform {
    /** @var bool */
    private $internalusage = false;

    /**
     * unplag_defaults_form constructor.
     *
     * @param object|null $mform - Moodle form
     */
    public function __construct($mform = null) {
        parent::__construct();

        if (!is_null($mform)) {
            $this->_form = $mform;
            $this->internalusage = true;
        }
    }

    // Define the form.
    /**
     * @throws coding_exception
     */
    public function definition() {
        $mform = &$this->_form;

        $ynoptions = [get_string('no'), get_string('yes')];
        $tiioptions = [
            get_string("never"), get_string("always"), plagiarism_unplag::trans("showwhenclosed"),
        ];
        $mform->addElement('header', 'plagiarismdesc', plagiarism_unplag::trans('unplag'));
        $mform->addElement('select', 'use_unplag', plagiarism_unplag::trans("useunplag"), $ynoptions);
        $mform->addElement('select', 'unplag_show_student_score',
            plagiarism_unplag::trans("unplag_show_student_score"), $tiioptions
        );
        $mform->addHelpButton('unplag_show_student_score', 'unplag_show_student_score', 'plagiarism_unplag');
        $mform->addElement('select', 'unplag_show_student_report',
            plagiarism_unplag::trans("unplag_show_student_report"), $tiioptions
        );
        $mform->addHelpButton('unplag_show_student_report', 'unplag_show_student_report', 'plagiarism_unplag');
        $mform->addElement('select', 'unplag_studentemail', plagiarism_unplag::trans("unplag_studentemail"), $ynoptions);
        $mform->addHelpButton('unplag_studentemail', 'unplag_studentemail', 'plagiarism_unplag');

        if (!$this->internalusage) {
            $this->add_action_buttons(true);
        }
    }
}