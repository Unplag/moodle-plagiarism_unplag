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
 * unplag_tabs.php - tabs used in admin interface.
 *
 * @package   plagiarism_unplag
 * @author     Mikhail Grinenko <m.grinenko@p1k.co.uk>
 * @copyright  UKU Group, LTD, https://www.unplag.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

$strplagiarism = get_string('unplag', 'plagiarism_unplag');
$strplagiarismdefaults = get_string('unplagdefaults', 'plagiarism_unplag');
$strplagiarismdebug = get_string('unplagdebug', 'plagiarism_unplag');

$tabs = array();
$tabs[] = new tabobject('unplagsettings', 'settings.php', $strplagiarism, $strplagiarism, false);
$tabs[] = new tabobject('unplagdefaults', 'unplag_defaults.php', $strplagiarismdefaults, $strplagiarismdefaults, false);
$tabs[] = new tabobject('unplagdebug', 'unplag_debug.php', $strplagiarismdebug, $strplagiarismdebug, false);
print_tabs(array($tabs), $currenttab);