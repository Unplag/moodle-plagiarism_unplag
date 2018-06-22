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
 * settings.php - allows the admin to configure plagiarism stuff
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use plagiarism_unplag\classes\unplag_notification;

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/unplag_form.php');

global $CFG, $OUTPUT, $USER;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');

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
require_once(dirname(__FILE__) . '/views/view_tabs.php');

if (($data = $mform->get_data()) && confirm_sesskey()) {
    foreach (plagiarism_plugin_unplag::default_plugin_options() as $option) {
        if (!isset($data->$option)) {
            $data->$option = 0;
        }
    }

    foreach ($data as $field => $value) {
        if (strpos($field, 'unplag') === 0) {
            set_config($field, $value, 'plagiarism');
        }
    }

    unplag_notification::success('savedconfigsuccess', true);
}

$mform->set_data(get_config('plagiarism'));

echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
