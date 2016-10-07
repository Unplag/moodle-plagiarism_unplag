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
 * default_settings.php - Displays default values to use inside assignments for UNPLAG
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use plagiarism_unplag\classes\unplag_notification;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG, $DB, $OUTPUT;

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/unplag_form.php');

require_login();
admin_externalpage_setup('plagiarismunplag');

$context = context_system::instance();

$mform = new unplag_defaults_form(null);
// The cmid(0) is the default list.
$unplagdefaults = $DB->get_records_menu(UNPLAG_CONFIG_TABLE, array('cm' => 0), '', 'name, value');
if (!empty($unplagdefaults)) {
    $mform->set_data($unplagdefaults);
}
echo $OUTPUT->header();
$currenttab = 'unplagdefaults';
require_once(dirname(__FILE__) . '/views/view_tabs.php');

if (($data = $mform->get_data()) && confirm_sesskey()) {
    $plagiarismelements = plagiarism_plugin_unplag::config_options();
    foreach ($plagiarismelements as $element) {
        if (isset($data->$element)) {
            $newelement = new Stdclass();
            $newelement->cm = 0;
            $newelement->name = $element;
            $newelement->value = $data->$element;
            if (isset($unplagdefaults[$element])) {
                $newelement->id = $DB->get_field(UNPLAG_CONFIG_TABLE, 'id', (array('cm' => 0, 'name' => $element)));
                $DB->update_record(UNPLAG_CONFIG_TABLE, $newelement);
            } else {
                $DB->insert_record(UNPLAG_CONFIG_TABLE, $newelement);
            }
        }
    }

    unplag_notification::success('defaultupdated', true);
}
echo $OUTPUT->box(plagiarism_unplag::trans('defaultsdesc'));

$mform->display();
echo $OUTPUT->footer();