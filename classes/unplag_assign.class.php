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

namespace plagiarism_unplag\classes;

use assign;
use context_module;

/**
 * Class unplag_assign
 *
 * @package plagiarism_unplag\classes
 * @subpackage  plagiarism
 * @namespace plagiarism_unplag\classes
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_assign {
    /**
     * @param $cmid
     * @param null $userid
     * @return bool|\stdClass
     */
    public static function get_user_submission_by_cmid($cmid, $userid = null) {
        global $USER;

        try {
            $modulecontext = context_module::instance($cmid);
            $assign = new assign($modulecontext, false, false);
        } catch (\Exception $ex) {
            return false;
        }

        return ($assign->get_user_submission(($userid !== null) ? $userid : $USER->id, false));
    }
}