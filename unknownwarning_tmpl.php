<?php
global $OUTPUT;

$title = get_string('unknownwarning', 'plagiarism_unplag');
$reset = '';
$modulecontext = context_module::instance($linkarray['cmid']);
// This is a teacher viewing the responses.
if (has_capability('plagiarism/unplag:resetfile', $modulecontext) && !empty($fileobj->errorresponse)) {
    // Strip out some possible known text to tidy it up.
    if (is_array($errors)) {
        $erroresponse = format_text(implode(',', array_values($errors)), FORMAT_PLAIN);
    } else {
        $erroresponse = get_string('unknownwarning', 'plagiarism_unplag');
    }

    $erroresponse = str_replace('{&quot;LocalisedMessage&quot;:&quot;', '', $erroresponse);
    $erroresponse = str_replace('&quot;,&quot;Message&quot;:null}', '', $erroresponse);
    $title .= ': ' . $erroresponse;
    $url = new moodle_url('/plagiarism/unplag/reset.php', ['cmid' => $cmid, 'pf' => $fileobj->id, 'sesskey' => sesskey()]);
    $reset = " <a href='$url'><img src='" . $OUTPUT->pix_url('reset', 'plagiarism_unplag') . "' title='" . get_string('reset') . "'></a>";
}

$html_parts[] = '<span class="un_report">';
$html_parts[] = sprintf('<img class="un_tooltip" src="%1$s" alt="%2$s" title="%3$s" />%4$s',
    $OUTPUT->pix_url('error', 'plagiarism_unplag'),
    get_string('unknownwarning', 'plagiarism_unplag'), $title, $reset
);

$html_parts[] = '</span>';

return implode('', $html_parts);