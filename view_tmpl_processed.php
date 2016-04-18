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

global $OUTPUT, $USER;

// Normal situation - UNPLAG has successfully analyzed the file.
$htmlparts = ['<span class="un_report">'];

if (!empty($fileobj->reporturl) || !empty($fileobj->similarityscore)) {
    // User is allowed to view the report.
    // Score is contained in report, so they can see the score too.
    $htmlparts[] = '<img  width="32" height="32" src="' . $OUTPUT->pix_url('unplag', 'plagiarism_unplag') . '"> ';

    if (!empty($fileobj->similarityscore)) {
        // User is allowed to view only the score.
        $htmlparts[] = sprintf('%s: <span class="rank1">%s%%</span>',
            get_string('similarity', 'plagiarism_unplag'),
            $fileobj->similarityscore
        );
    }

    if (!empty($fileobj->reporturl)) {
        $modulecontext = context_module::instance($linkarray['cmid']);
        // This is a teacher viewing the responses.
        $teacherhere = has_capability('moodle/grade:edit', $modulecontext, $USER->id);
        // Display opt-out link.
        $htmlparts[] = '&nbsp;<span class"plagiarismoptout">';
        $htmlparts[] = sprintf('<a title="%s" href="%s" target="_blank">',
            get_string('report', 'plagiarism_unplag'), $teacherhere ? $fileobj->reportediturl : $fileobj->reporturl
        );
        $htmlparts[] = '<img class="un_tooltip" src="' . $OUTPUT->pix_url('link', 'plagiarism_unplag') . '">';
        $htmlparts[] = '</a></span>';
    }
}

$htmlparts[] = '</span>';

return implode('', $htmlparts);