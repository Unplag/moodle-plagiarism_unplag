<?php

$html_parts[] = '<span class="un_report">';
$html_parts[] = sprintf('<img class="un_tooltip" src="%1$s" alt="%2$s" title="%2$s" />',
    $OUTPUT->pix_url('error', 'plagiarism_unplag'), get_string('unsupportedfiletype', 'plagiarism_unplag')
);
$html_parts[] = '</span>';

return implode('', $html_parts);