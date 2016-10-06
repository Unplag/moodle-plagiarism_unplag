<?php

use plagiarism_unplag\classes\helpers\unplag_stored_file;
use plagiarism_unplag\classes\unplag_language;

global $PAGE, $CFG, $OUTPUT, $USER;

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once(dirname(__FILE__) . '/lib.php');

$PAGE->set_pagelayout('report');

$cmid = required_param('cmid', PARAM_INT);  // Course Module ID
$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, true, $cm);

$pf = required_param('pf', PARAM_INT);   // plagiarism file id.
$cpf = required_param('cpf', PARAM_INT);   // plagiarism file id.
$childs = unplag_stored_file::get_childs($pf);
$current = unplag_stored_file::get_unplag_file($cpf);

$modulecontext = context_module::instance($cmid);

echo $OUTPUT->header();

$currenttab = 'unplag_file_id_' . $current->id;
$tabs = array();

foreach ($childs as $child) {

    $url = new \moodle_url('/plagiarism/unplag/reports.php', array(
            'cmid' => $cmid,
            'pf' => $pf,
            'cpf' => $child->id
    ));

    $tabs[] = new tabobject('unplag_file_id_' . $child->id, $url->out_as_local_url(), $child->filename, '', false);
};
print_tabs(array($tabs), $currenttab);

$teacherhere = has_capability('moodle/grade:edit', $modulecontext, $USER->id);
$reporturl = $current->reporturl;
if ($teacherhere) {
    $reporturl = $current->reportediturl;
}
unplag_language::inject_language_to_url($reporturl);

echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
echo '<iframe src="' . $reporturl .
        '" frameborder="0" allowfullscreen align="center" id="_unplag_report_frame" style="width: 100%; height: 750px;"></iframe>';
echo $OUTPUT->box_end();

echo $OUTPUT->footer();