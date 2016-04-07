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
 * checkreceiver.php - Checks to make sure passed receiver address is valid.
 *
 * @since      2.0
 * @package    plagiarism_unplag
 * @subpackage plagiarism
 * @author     Mikhail Grinenko <m.grinenko@p1k.co.uk>
 * @copyright  UKU Group, LTD, https://www.unplag.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('locallib.php');

$action = required_param('action', PARAM_ALPHAEXT);
$data = required_param('data', PARAM_RAW);

require_login();
require_sesskey();

$unplag = new plagiarism_unplag();
if (!is_callable([$unplag, $action])) {
    echo json_encode('Called method does not exists');
    return null;
}
echo $unplag->{$action}($data);