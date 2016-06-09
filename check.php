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
 * reset.php - resets an unplag submission
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unplag\classes\unplag_core;

global $PAGE, $CFG;

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once(dirname(__FILE__) . '/lib.php');

$cmid = required_param('cmid', PARAM_INT);  // Course Module ID
$pf = required_param('pf', PARAM_INT);   // plagiarism file id.

require_sesskey();

$url = new moodle_url(dirname(__FILE__) . '/check.php');
$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);

$PAGE->set_url($url);
require_login($cm->course, true, $cm);

$modulecontext = context_module::instance($cmid);
require_capability('plagiarism/unplag:checkfile', $modulecontext);

unplag_core::check_submitted_assignment($pf);

if ($cm->modname == 'assignment') {
    $redirect = new moodle_url('/mod/assignment/submissions.php', ['id' => $cmid]);
} else if ($cm->modname == 'assign') {
    $redirect = new moodle_url('/mod/assign/view.php', ['id' => $cmid, 'action' => 'grading']);
} else {
    // TODO: add correct locations for workshop and forum.
    $redirect = $CFG->wwwroot;
}

redirect($redirect, plagiarism_unplag::trans('check_start'));