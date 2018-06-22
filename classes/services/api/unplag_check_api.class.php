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
 * unplag_check_api.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\services\api;

use plagiarism_unplag\classes\unplag_api;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_check_api
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_check_api {

    /**
     * Get checks throw API
     *
     * @param array $ids
     *
     * @return array
     */
    public function get_finished_check_by_ids($ids) {
        $checklist = [];
        $apirequest = new unplag_api();
        $checkprogresslist = $apirequest->get_check_progress($ids);
        if ($checkprogresslist->progress && $checkprogresslist->result) {
            foreach ($ids as $checkid) {
                if (isset($checkprogresslist->progress->$checkid) && ($checkprogresslist->progress->$checkid == 1)) {
                    $check = $apirequest->get_check_data($checkid);
                    array_push($checklist, $check);
                }
            }
        }

        return $checklist;
    }
}