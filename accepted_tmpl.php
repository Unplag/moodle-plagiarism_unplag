<?php

global $PAGE;
// Now add JS to validate receiver indicator using Ajax.
$jsmodule = [
    'name'     => 'plagiarism_unplag',
    'fullpath' => '/plagiarism/unplag/ajax.js',
    'requires' => ['json'],
];

$PAGE->requires->js_init_call('M.plagiarism_unplag.init', [$linkarray['cmid']], true, $jsmodule);

$html_parts[] = '<span class="un_report">';
$html_parts[] = sprintf('<img  class="%1$s un_progress un_tooltip" src="%2$s" alt="%3$s" title="%3$s" file_id="%4$d" />',
    $fileobj->id,
    $OUTPUT->pix_url('scan', 'plagiarism_unplag'),
    get_string('processing', 'plagiarism_unplag'),
    $fileobj->id
);
$html_parts[] = sprintf('%s: <span file_id="%d" class="un_progress_val" >%d%%</span>',
    get_string('progress', 'plagiarism_unplag'),
    $fileobj->id, intval($fileobj->progress)
);

$html_parts[] = '</span>';

return implode('', $html_parts);