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
 * view_tmpl_invalid_response.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $OUTPUT;

$errors = isset($fileobj->errorresponse) ? json_decode($fileobj->errorresponse, true) : null;
if (is_array($errors)) {
    $erroresponse = 'Error: ' . $errors[0]['message'];
} else {
    $erroresponse = get_string('unknownwarning', 'plagiarism_unplag');
}

$htmlparts = ['<span class="un_report">'];
$htmlparts[] = sprintf('<img class="un_tooltip" src="%1$s" alt="%2$s" title="%2$s" />',
    $OUTPUT->pix_url('error', 'plagiarism_unplag'), $erroresponse
);
$htmlparts[] = '</span>';

return implode('', $htmlparts);