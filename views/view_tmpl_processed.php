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
 * view_tmpl_processed.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_language;
use plagiarism_unplag\classes\unplag_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $OUTPUT, $USER, $PAGE;

if (AJAX_SCRIPT) {
    $PAGE->set_context(null);
}

// Normal situation - UNPLAG has successfully analyzed the file.
$htmlparts = array('<span class="un_report">');

if (empty($cid) && !empty($linkarray['cmid'])) {
    $cid = $linkarray['cmid'];
}

if (!empty($cid) && !empty($fileobj->reporturl) || !empty($fileobj->similarityscore)) {
    // User is allowed to view the report.
    // Score is contained in report, so they can see the score too.
    $htmlparts[] = sprintf('<img  width="32" height="32" src="%s" title="%s"> ',
            $OUTPUT->pix_url('unplag', 'plagiarism_unplag'), plagiarism_unplag::trans('pluginname')
    );

    $modulecontext = context_module::instance($cid);
    // This is a teacher viewing the responses.
    $teacherhere = has_capability('moodle/grade:edit', $modulecontext, $USER->id);
    $assigncfg = unplag_settings::get_assign_settings($cid, null, true);

    if (isset($fileobj->similarityscore)) {
        if ($teacherhere || $assigncfg['unplag_show_student_score']) {
            // User is allowed to view only the score.
            $htmlparts[] = sprintf('%s: <span class="rank1">%s%%</span>',
                    plagiarism_unplag::trans('similarity'),
                    $fileobj->similarityscore
            );
        }
    }

    if (!empty($fileobj->reporturl)) {

        $reporturl = $fileobj->reporturl;
        $editreporturl = $fileobj->reportediturl;

        unplag_language::inject_language_to_url($reporturl);
        unplag_language::inject_language_to_url($editreporturl);
        if ($teacherhere) {
            unplag_core::inject_comment_token($editreporturl);
        }

        if ($teacherhere || $assigncfg['unplag_show_student_report']) {
            // Display opt-out link.
            $htmlparts[] = '&nbsp;<span class"plagiarismoptout">';
            $htmlparts[] = sprintf('<a title="%s" href="%s" target="_blank">',
                    plagiarism_unplag::trans('report'), $teacherhere ? $editreporturl : $reporturl
            );
            $htmlparts[] = '<img class="un_tooltip" src="' . $OUTPUT->pix_url('link', 'plagiarism_unplag') . '">';
            $htmlparts[] = '</a></span>';
        }
    }
}

$htmlparts[] = '</span>';

return implode('', $htmlparts);