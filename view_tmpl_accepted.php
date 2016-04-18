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
 * view_tmpl_accepted.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $PAGE, $OUTPUT;

if (!$iterator) {
    // Now add JS to validate receiver indicator using Ajax.
    $jsmodule = [
        'name'     => 'plagiarism_unplag',
        'fullpath' => '/plagiarism/unplag/ajax.js',
        'requires' => ['json'],
    ];

    $PAGE->requires->js_init_call('M.plagiarism_unplag.init', [$linkarray['cmid']], true, $jsmodule);
}

$htmlparts = ['<span class="un_report">'];
$htmlparts[] = sprintf('<img  class="%1$s un_progress un_tooltip" src="%2$s" alt="%3$s" title="%3$s" file_id="%4$d" />',
    $fileobj->id,
    $OUTPUT->pix_url('loader', 'plagiarism_unplag'),
    get_string('processing', 'plagiarism_unplag'),
    $fileobj->id
);
$htmlparts[] = sprintf('%s: <span file_id="%d" class="un_progress_val" >%d%%</span>',
    get_string('progress', 'plagiarism_unplag'),
    $fileobj->id, intval($fileobj->progress)
);

$htmlparts[] = '</span>';

return implode('', $htmlparts);