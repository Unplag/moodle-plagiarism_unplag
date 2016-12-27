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
 * unplag_bulk_check_assign_files.class.php
 *
 * @package     plagiarism_unplag
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\task;

use core\task\adhoc_task;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_assign;
use plagiarism_unplag\classes\unplag_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_bulk_check_assign_files
 * @package plagiarism_unplag\classes\task
 */
class unplag_bulk_check_assign_files extends adhoc_task {
    public function execute() {
        $data = $this->get_custom_data();

        $assignfiles = unplag_assign::get_area_files($data->contextid);

        if (empty($assignfiles)) {
            return;
        }

        foreach ($assignfiles as $assignfile) {
            mtrace('File with uuid ' . $assignfile->get_pathnamehash() . ' ready to send');
            $unplagcore = new unplag_core($data->cmid, $assignfile->get_userid());
            $plagiarismentity = $unplagcore->get_plagiarism_entity($assignfile);
            $internalfile = $plagiarismentity->upload_file_on_unplag_server();
            if (isset($internalfile->check_id)) {
                mtrace('File with uuid ' . $internalfile->identifier . ' already sent to Unplag');
            } else if ($internalfile->external_file_id) {
                $checkresp = unplag_api::instance()->run_check($internalfile);
                $plagiarismentity->handle_check_response($checkresp);
                mtrace('File ' . $internalfile->identifier . ' send to Unplag');
            }
        }
    }
}