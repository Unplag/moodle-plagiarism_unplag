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
 * unplag_defaults.php - Displays default values to use inside assignments for UNPLAG
 *
 * @package plagiarism_unplag
 * @authors  Dan Marsden <Dan@danmarsden.com>, Mikhail Grinenko <m.grinenko@p1k.co.uk>
 * @copyright 2014 Dan Marsden <Dan@danmarsden.com>, 
 * @copyright   UKU Group, LTD, https://www.unplag.com 
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/plagiarism/unplag/lib.php');
require_once('unplag_form.php');

require_login();
admin_externalpage_setup('plagiarismunplag');

$context = context_system::instance();

$mform = new unplag_defaults_form(null);
$plagiarismdefaults = $DB->get_records_menu('plagiarism_unplag_config',
    array('cm' => 0), '', 'name, value'); // The cmid(0) is the default list.
if (!empty($plagiarismdefaults)) {
    $mform->set_data($plagiarismdefaults);
}
echo $OUTPUT->header();
$currenttab = 'unplagdefaults';
require_once('unplag_tabs.php');
if (($data = $mform->get_data()) && confirm_sesskey()) {
    $plagiarismplugin = new plagiarism_plugin_unplag();

    $plagiarismelements = $plagiarismplugin->config_options();
    foreach ($plagiarismelements as $element) {
        if (isset($data->$element)) {
            $newelement = new Stdclass();
            $newelement->cm = 0;
            $newelement->name = $element;
            $newelement->value = $data->$element;
            if (isset($plagiarismdefaults[$element])) {
                $newelement->id = $DB->get_field('plagiarism_unplag_config', 'id', (array('cm' => 0, 'name' => $element)));
                $DB->update_record('plagiarism_unplag_config', $newelement);
            } else {
                $DB->insert_record('plagiarism_unplag_config', $newelement);
            }
        }
    }
    echo $OUTPUT->notification(get_string('defaultupdated', 'plagiarism_unplag'), 'notifysuccess');
}
echo $OUTPUT->box(get_string('defaultsdesc', 'plagiarism_unplag'));

$mform->display();
echo $OUTPUT->footer();