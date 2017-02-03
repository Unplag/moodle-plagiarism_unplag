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

use plagiarism_unplag\classes\unplag_settings;

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
        $mform = &$this->_form;
        $mform->addElement('checkbox', 'unplag_use', plagiarism_unplag::trans('use_unplag'));

        $settingstext = '<div id="fitem_id_unplag_settings_link" class="fitem fitem_ftext ">
                            <div class="felement ftext">
                                <a href="' . UNPLAG_DOMAIN . 'profile/apisettings" target="_blank"> ' .
            plagiarism_unplag::trans('unplag_settings_url_text') . '</a>
                            </div>
                        </div>';
        $mform->addElement('html', $settingstext);

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
        foreach (array_keys($mods) as $mod) {
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
    /** @var string */
    private $modname = '';

    /**
     * unplag_defaults_form constructor.
     *
     * @param object|null $mform - Moodle form
     * @param string|null $modname
     */
    public function __construct($mform = null, $modname = null) {
        parent::__construct();

        if (!is_null($mform)) {
            $this->_form = $mform;
            $this->internalusage = true;
        }

        if (!is_null($modname) && is_string($modname) && plagiarism_plugin_unplag::is_enabled_module($modname)) {
            $modname = str_replace('mod_', '', $modname);
            if (plagiarism_unplag::is_support_mod($modname)) {
                $this->modname = $modname;
            };
        }
    }

    // Define the form.
    /**
     * @throws coding_exception
     */
    public function definition() {

        $defaultsforfield = function (MoodleQuickForm $mform, $setting, $defaultvalue) {
            if (!isset($mform->exportValues()[$setting]) || is_null($mform->exportValues()[$setting])) {
                $mform->setDefault($setting, $defaultvalue);
            }
        };

        $addYesNoElem = function (MoodleQuickForm $mform, $setting, $showHelpBalloon = false) {
            $ynoptions = array(get_string('no'), get_string('yes'));
            $mform->addElement('select', $setting, plagiarism_unplag::trans($setting), $ynoptions);
            if ($showHelpBalloon) {
                $mform->addHelpButton($setting, $setting, 'plagiarism_unplag');
            }
        };

        /** @var MoodleQuickForm $mform */
        $mform = &$this->_form;

        $mform->addElement('header', 'plagiarismdesc', plagiarism_unplag::trans('unplag'));

        if ($this->modname === UNPLAG_MODNAME_ASSIGN) {
            $mform->addElement('static', 'use_unplag_static_description', plagiarism_unplag::trans('useunplag_assign_desc_param'),
                plagiarism_unplag::trans('useunplag_assign_desc_value'));
        }

        $setting = unplag_settings::USE_UNPLAG;
        $addYesNoElem($mform, $setting);
        if ($this->modname === UNPLAG_MODNAME_ASSIGN) {
            $mform->addHelpButton($setting, $setting, 'plagiarism_unplag');
        }

        if (!in_array($this->modname, array(UNPLAG_MODNAME_FORUM, UNPLAG_MODNAME_WORKSHOP))) {
            $addYesNoElem($mform, unplag_settings::CHECK_ALL_SUBMITTED_ASSIGNMENTS);
        }

        $setting = unplag_settings::CHECK_TYPE;
        $mform->addElement('select', $setting, plagiarism_unplag::trans($setting), array(
            UNPLAG_CHECK_TYPE_WEB__LIBRARY => plagiarism_unplag::trans(UNPLAG_CHECK_TYPE_WEB__LIBRARY),
            UNPLAG_CHECK_TYPE_WEB          => plagiarism_unplag::trans(UNPLAG_CHECK_TYPE_WEB),
            UNPLAG_CHECK_TYPE_MY_LIBRARY   => plagiarism_unplag::trans(UNPLAG_CHECK_TYPE_MY_LIBRARY),
        ));

        $addYesNoElem($mform, unplag_settings::SHOW_STUDENT_SCORE, true);
        $addYesNoElem($mform, unplag_settings::SHOW_STUDENT_REPORT, true);

        $setting = unplag_settings::SENSITIVITY_SETTING_NAME;
        $mform->addElement('text', $setting, plagiarism_unplag::trans($setting));
        $mform->setType($setting, PARAM_TEXT);
        $defaultsforfield($mform, $setting, 0);

        $setting = unplag_settings::EXCLUDE_CITATIONS;
        $addYesNoElem($mform, $setting);
        $defaultsforfield($mform, $setting, 1);

        if (!in_array($this->modname, array(UNPLAG_MODNAME_FORUM, UNPLAG_MODNAME_WORKSHOP))) {
            $addYesNoElem($mform, unplag_settings::NO_INDEX_FILES);
        }

        if (!$this->internalusage) {
            $this->add_action_buttons(true);
        }
    }
}