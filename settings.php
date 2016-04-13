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
 * plagiarism.php - allows the admin to configure plagiarism stuff
 *
 * @package     plagiarism_unplag
 * @author      Dan Marsden <Dan@danmarsden.com>
 * @author      Mikhail Grinenko <m.grinenko@p1k.co.uk>
 * @copyright   2014 Dan Marsden <Dan@danmarsden.com>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');
require_once($CFG->dirroot . '/plagiarism/unplag/lib.php');
require_once($CFG->dirroot . '/plagiarism/unplag/unplag_form.php');

require_login();
admin_externalpage_setup('plagiarismunplag');

$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

$mform = new unplag_setup_form();
$plagiarismplugin = new plagiarism_plugin_unplag();

if ($mform->is_cancelled()) {
    redirect('');
}

echo $OUTPUT->header();
$currenttab = 'unplagsettings';
require_once('unplag_tabs.php');

if (($data = $mform->get_data()) && confirm_sesskey()) {
    foreach (plagiarism_plugin_unplag::default_plagin_options() as $option) {
        if (!isset($data->$option)) {
            $data->$option = 0;
        }
    }

    foreach ($data as $field => $value) {
        if (strpos($field, 'unplag') === 0) {
            set_config($field, $value, 'plagiarism_unplag');
        }
    }
    echo $OUTPUT->notification(get_string('savedconfigsuccess', 'plagiarism_unplag'), 'notifysuccess');
}

$plagiarismsettings = (array)get_config('plagiarism_unplag');
$mform->set_data($plagiarismsettings);

echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
