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
 * Local language pack from http://moodle2.lnu.se
 *
 * @package    plagiarism
 * @subpackage unplag
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['defaultsdesc'] = 'Följande inställningar är förinställda då UNPLAG aktiveras i en aktivitetsmodul';
$string['filereset'] = 'En fil har återställts för att åter skickas till UNPLAG';
$string['pending'] = 'Den här filen väntar på att skickas till UNPLAG';
$string['pluginname'] = 'Plugin för UNPLAG plagieringskontroll';
$string['processing'] = 'Filen har skickats till UNPLAG, vänta tills analysen är klar.';
$string['similarity'] = 'UNPLAG';
$string['studentemailsubject'] = 'Filer granskade via UNPLAG';
$string['submitondraft'] = 'Skicka till UNPLAG direkt när filen laddas upp.';
$string['submitonfinal'] = 'Skicka till UNPLAG när studenten valt att skicka in för betygssättning.';
$string['toolarge'] = 'Den här filen är för stor för UNPLAG';
$string['unknownwarning'] = 'Ett fel inträffade då den här filen skulle skickas till UNPLAG';
$string['unsupportedfiletype'] = 'Filtypen stöds inte av UNPLAG';
$string['unplag'] = 'Plugin för UNPLAG plagieringskontroll';
$string['unplagdefaults'] = 'UNPLAG grundinställningar';
$string['unplag_draft_submit'] = 'När ska filen skickas till UNPLAG';
$string['unplag_receiver'] = 'Mottagaradress (konto hos UNPLAG)';
$string['unplag_show_student_report'] = 'Visa jämförelserapport för student';
$string['unplag_show_student_report_help'] = 'Jämförelserapporten visar vilka avsnitt av den uppladdade filen som ansågs som plagiat och var UNPLAG hitta originalen.';
$string['unplag_show_student_score'] = 'Visa jämförelseprocent för student';
$string['unplag_show_student_score_help'] = 'Här visas hur stor procent av den inskickade filen som UNPLAG hittat matchningar med.';
$string['unplag_studentemail'] = 'Skicka epost till student';
$string['unplag_studentemail_help'] = 'Studenten får ett epostmeddelande då granskningen är klar. Meddelandet innehåller även opt-out-länk, om studenten inte vill att hans dokument ska kunna användas för framtida jämförelser i UNPLAG.';
$string['useunplag'] = 'Aktivera UNPLAG';
