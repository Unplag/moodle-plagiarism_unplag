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
 * view_tabs.php - tabs used in admin interface.
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

$strplagiarism = plagiarism_unplag::trans('unplag');
$strplagiarismdefaults = plagiarism_unplag::trans('unplagdefaults');
$strplagiarismdebug = plagiarism_unplag::trans('unplagdebug');

$tabs = array(
        new tabobject('unplagsettings', 'settings.php', $strplagiarism, $strplagiarism, false),
        new tabobject('unplagdefaults', 'default_settings.php', $strplagiarismdefaults, $strplagiarismdefaults, false),
        new tabobject('unplagdebug', 'debugging.php', $strplagiarismdebug, $strplagiarismdebug, false),
);
print_tabs(array($tabs), $currenttab);