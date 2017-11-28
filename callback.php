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
 * ajax.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$token = optional_param('token', '', PARAM_RAW);
if (!$token) {
    require_login();
    require_sesskey();
}

$body = \plagiarism_unplag\classes\unplag_core::parse_json(file_get_contents('php://input'));
if (!is_object($body)) {
    http_response_code(400);
    echo 'Invalid callback body';

    die;
}

$callback = new \plagiarism_unplag\classes\unplag_callback();
try {
    $callback->handle($body, $token);
} catch (\Exception $exception) {
    http_response_code(400);
    echo $exception->getMessage();
}

die;