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
 * unplag_plagiarism_entity.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unplag\classes\helpers\unplag_stored_file;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_language;

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once(dirname(__FILE__) . '/lib.php');

global $PAGE, $CFG, $OUTPUT, $USER;

$PAGE->set_pagelayout('report');

$cmid = required_param('cmid', PARAM_INT);  // Course Module ID.
$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, true, $cm);

$pf = required_param('pf', PARAM_INT);   // Plagiarism file id.
$cpf = required_param('cpf', PARAM_INT);   // Plagiarism child file id.
$childs = unplag_stored_file::get_childs($pf);
$current = unplag_stored_file::get_unplag_file($cpf);

$modulecontext = context_module::instance($cmid);

echo $OUTPUT->header();

$currenttab = 'unplag_file_id_' . $current->id;
$tabs = array();

foreach ($childs as $child) {

    if ($child->check_id !== null && $child->progress == 100) {
        $url = new \moodle_url('/plagiarism/unplag/reports.php', array(
                'cmid' => $cmid,
                'pf' => $pf,
                'cpf' => $child->id
        ));

        $tabs[] = new tabobject('unplag_file_id_' . $child->id, $url->out_as_local_url(), $child->filename, '', false);
    }
};
print_tabs(array($tabs), $currenttab);

$teacherhere = has_capability('moodle/grade:edit', $modulecontext, $USER->id);
$reporturl = $current->reporturl;
if ($teacherhere) {
    $reporturl = $current->reportediturl;
    unplag_core::inject_comment_token($reporturl);
}
unplag_language::inject_language_to_url($reporturl, 0);

echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
echo '<iframe src="' . $reporturl .
        '" frameborder="0" allowfullscreen align="center" id="_unplag_report_frame" style="width: 100%; height: 750px;"></iframe>';
echo $OUTPUT->box_end();

echo $OUTPUT->footer();