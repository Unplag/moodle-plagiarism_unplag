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
 * debugging.php - Displays default values to use inside assignments for UNPLAG
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_notification;

global $CFG, $DB, $OUTPUT;

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/plagiarismlib.php');
require_once($CFG->libdir . '/datalib.php');
require_once(dirname(__FILE__) . '/lib.php');

require_login();
admin_externalpage_setup('plagiarismunplag');

$id = optional_param('id', 0, PARAM_INT);
$resetuser = optional_param('reset', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$sort = optional_param('tsort', '', PARAM_ALPHA);
$dir = optional_param('dir', '', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);

$exportfilename = 'UnplagDebugOutput.csv';
$table = new flexible_table('unplagfiles');
if (!$table->is_downloading($download, $exportfilename)) {
    echo $OUTPUT->header();
    $currenttab = 'unplagdebug';

    require_once(dirname(__FILE__) . '/view_tabs.php');

    // Get list of Events in queue.
    $a = new stdClass();
    $a->countallevents = $DB->count_records('events_queue_handlers');
    $a->countheld = $DB->count_records_select('events_queue_handlers', 'status > 0');

    $warning = '';
    if (!empty($a->countallevents)) {
        $warning = ' warning';
    }

    if ($resetuser == 1 && $id && confirm_sesskey()) {
        if (unplag_core::resubmit_file($id)) {
            unplag_notification::error('fileresubmitted', true);
        }
    } else {
        if ($resetuser == 2 && $id && confirm_sesskey()) {
            $plagiarismfile = $DB->get_record(UNPLAG_FILES_TABLE, array('id' => $id), '*', MUST_EXIST);
            $response = unplag_api::instance()->get_check_data($plagiarismfile->check_id);
            if ($response->result) {
                unplag_core::check_complete($plagiarismfile, $response->check);
            } else {
                $plagiarismfile->errorresponse = json_encode($response->errors);
                $DB->update_record(UNPLAG_FILES_TABLE, $plagiarismfile);
            }

            if ($plagiarismfile->statuscode == UNPLAG_STATUSCODE_ACCEPTED) {
                unplag_notification::error('scorenotavailableyet', true);
            } else {
                if ($plagiarismfile->statuscode == UNPLAG_STATUSCODE_PROCESSED) {
                    unplag_notification::error('scoreavailable', true);
                } else {
                    unplag_notification::error('unknownwarning', true);
                }
            }
        }
    }

    if (!empty($delete) && confirm_sesskey()) {
        $DB->delete_records(UNPLAG_FILES_TABLE, array('id' => $id));
        unplag_notification::success('filedeleted', true);
    }
}
$heldevents = array();

// Now do sorting if specified.
$orderby = '';
switch ($sort) {
    case 'name':
        $orderby = " ORDER BY u.firstname, u.lastname";
        break;
    case 'module':
        $orderby = " ORDER BY cm.id";
        break;
    case 'status':
        $orderby = " ORDER BY t.errorresponse";
        break;
    case 'id':
        $orderby = " ORDER BY t.id";
        break;
}

if (!empty($orderby) && ($dir == 'asc' || $dir == 'desc')) {
    $orderby .= " " . $dir;
}

// Now show files in an error state.
$sql = sprintf('SELECT t.*, %1$s, m.name as moduletype, cm.course as courseid, cm.instance as cminstance
    FROM {plagiarism_unplag_files} t, {user} u, {modules} m, {course_modules} cm
    WHERE m.id=cm.module AND cm.id=t.cm AND t.userid=u.id
    AND t.errorresponse is not null
    %2$s',
        get_all_user_name_fields(true, 'u'), $orderby
);

$limit = 20;
$unplagfiles = $DB->get_records_sql($sql, null, $page * $limit, $limit);

$table->define_columns(array('id', 'name', 'module', 'identifier', 'status', 'attempts', 'action'));
$table->define_headers(array(
        plagiarism_unplag::trans('id'),
        get_string('user'),
        plagiarism_unplag::trans('module'),
        plagiarism_unplag::trans('identifier'),
        plagiarism_unplag::trans('status'),
        plagiarism_unplag::trans('attempts'), '',
));
$table->define_baseurl('debugging.php');
$table->sortable(true);
$table->no_sorting('file', 'action');
$table->collapsible(true);
$table->set_attribute('cellspacing', '0');
$table->set_attribute('class', 'generaltable generalbox');
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->setup();

$fs = get_file_storage();
foreach ($unplagfiles as $tf) {
    if ($table->is_downloading()) {
        $row = array(
                $tf->id,
                $tf->userid,
                $tf->cm . ' ' . $tf->moduletype,
                $tf->identifier,
                $tf->statuscode,
                $tf->attempt,
                $tf->errorresponse,
        );
    } else {

        $builddebuglink = function($tf, $action, $transtext) {
            return sprintf('<a href="debugging.php?%4$s&id=%1$s&sesskey=%2$s">%3$s</a>',
                    $tf->id, sesskey(), plagiarism_unplag::trans($transtext), $action
            );
        };

        if ($tf->statuscode == UNPLAG_STATUSCODE_ACCEPTED) { // Sanity Check.
            $action = 'reset=2';
            $transtext = 'getscore';
        } else {
            $action = 'reset=1';
            $transtext = 'resubmit';
        }
        $user = "<a href='" . $CFG->wwwroot . "/user/profile.php?id=" . $tf->userid . "'>" . fullname($tf) . "</a>";
        $cmurl = new moodle_url($CFG->wwwroot . '/mod/' . $tf->moduletype . '/view.php', array('id' => $tf->cm));
        $coursemodule = get_coursemodule_from_id($tf->moduletype, $tf->cm);
        $cmlink = html_writer::link($cmurl, shorten_text($coursemodule->name, 40, true), array('title' => $coursemodule->name));
        $reset = $builddebuglink($tf, $action, $transtext);
        $reset .= ' | ';
        $reset .= $builddebuglink($tf, 'delete=1', 'delete');

        $row = array(
                $tf->id,
                $user,
                $cmlink,
                $tf->identifier,
                $tf->errorresponse,
                $tf->attempt,
                $reset,
        );
    }

    $table->add_data($row);
}

if ($table->is_downloading()) {
    // Include some extra debugging information in the table.
    // Add some extra lines first.
    $table->add_data(array());
    $table->add_data(array());
    $table->add_data(array());
    $table->add_data(array());
    $table->add_data(array());
    $table->add_data(array());

    $configrecords = $DB->get_records(UNPLAG_CONFIG_TABLE);
    $table->add_data(array('id', 'cm', 'name', 'value'));
    foreach ($configrecords as $cf) {
        $table->add_data(array($cf->id, $cf->cm, $cf->name, $cf->value));
    }
}

if (!$table->is_downloading()) {
    echo $OUTPUT->heading(plagiarism_unplag::trans('unplagfiles'));
    echo $OUTPUT->box(plagiarism_unplag::trans('explainerrors'));
}

$table->finish_output();
if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}