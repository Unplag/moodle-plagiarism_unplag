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

$cmid = required_param('cmid', PARAM_INT); // Course Module ID.
$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, true, $cm);

$pf = required_param('pf', PARAM_INT); // Plagiarism file id.
$childs = unplag_stored_file::get_childs($pf);

$modulecontext = context_module::instance($cmid);

$pageparams = array('cmid' => $cmid, 'pf' => $pf);
$cpf = optional_param('cpf', null, PARAM_INT); // Plagiarism child file id.
if ($cpf !== null) {
    $current = unplag_stored_file::get_unplag_file($cpf);
    $currenttab = 'unplag_file_id_' . $current->id;
    $pageparams['cpf'] = $cpf;
} else {
    $currenttab = 'unplag_files_info';
}

$PAGE->set_pagelayout('report');
$pageurl = new \moodle_url('/plagiarism/unplag/reports.php', $pageparams);
$PAGE->set_url($pageurl);

echo $OUTPUT->header();

$tabs = array();
$fileinfos = array();
$canvieweditreport = unplag_core::can('plagiarism/unplag:vieweditreport', $cmid);
foreach ($childs as $child) {

    switch ($child->statuscode) {
        case UNPLAG_STATUSCODE_PROCESSED :

            $url = new \moodle_url('/plagiarism/unplag/reports.php', array(
                'cmid' => $cmid,
                'pf'   => $pf,
                'cpf'  => $child->id,
            ));

            if ($child->check_id !== null && $child->progress == 100) {

                $tabs[] = new tabobject('unplag_file_id_' . $child->id, $url->out(), $child->filename, '', false);

                $link = html_writer::link($url, $child->filename);
                $fileinfos[] = array(
                    'filename' => html_writer::tag('div', $link, array('class' => 'edit-link')),
                    'status'   => $OUTPUT->pix_icon('i/valid', plagiarism_unplag::trans('reportready')) .
                        plagiarism_unplag::trans('reportready'),
                );
            }
            break;
        case UNPLAG_STATUSCODE_INVALID_RESPONSE :

            $erroresponse = plagiarism_unplag::error_resp_handler($child->errorresponse);
            $fileinfos[] = array(
                'filename' => $child->filename,
                'status'   => $OUTPUT->pix_icon('i/invalid', $erroresponse) . $erroresponse,
            );
            break;
    }
};

$generalinfourl = new \moodle_url('/plagiarism/unplag/reports.php', array(
    'cmid' => $cmid,
    'pf'   => $pf,
));

array_unshift($tabs,
    new tabobject('unplag_files_info', $generalinfourl->out(), plagiarism_unplag::trans('generalinfo'), '', false));

print_tabs(array($tabs), $currenttab);

echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');

if ($cpf !== null) {
    $reporturl = $current->reporturl;
    if ($canvieweditreport) {
        $reporturl = $current->reportediturl;
    }
    unplag_core::inject_comment_token($reporturl, $cmid);
    unplag_language::inject_language_to_url($reporturl);

    echo '<iframe src="' . $reporturl . '" frameborder="0" id="_unplag_report_frame" style="width: 100%; height: 750px;"></iframe>';
} else {
    $table = new html_table();
    $table->head = array('Filename', 'Status');
    $table->align = array('left', 'left');

    foreach ($fileinfos as $fileinfo) {
        $linedata = array($fileinfo['filename'], $fileinfo['status']);
        $table->data[] = $linedata;
    }

    echo html_writer::table($table);
}
echo $OUTPUT->box_end();

echo $OUTPUT->footer();