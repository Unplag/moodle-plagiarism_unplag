<?php

// Normal situation - UNPLAG has successfully analyzed the file.
$html_parts[] = '<span class="un_report">';

if (!empty($fileobj->reporturl) || !empty($fileobj->similarityscore)) {
    // User is allowed to view the report.
    // Score is contained in report, so they can see the score too.
    $html_parts[] = '<img  width="32" height="32" src="' . $OUTPUT->pix_url('unplag', 'plagiarism_unplag') . '"> ';
}

if ($fileobj->similarityscore !== '') {
    // User is allowed to view only the score.
    $html_parts[] = sprintf('%s: <span class="rank1">%s%%</span>',
        get_string('similarity', 'plagiarism_unplag'),
        $fileobj->similarityscore
    );
}

if (!empty($fileobj->reporturl)) {
    // Display opt-out link.
    $html_parts[] = '&nbsp;<span class"plagiarismoptout">';
    $html_parts[] = sprintf('<a title="%s" href="%s" target="_blank">',
        get_string('report', 'plagiarism_unplag'), $fileobj->reporturl
    );
    $html_parts[] = '<img class="un_tooltip" src="' . $OUTPUT->pix_url('link', 'plagiarism_unplag') . '">';
    $html_parts[] = '</a></span>';
}

$html_parts[] = '</span>';

return implode('', $html_parts);