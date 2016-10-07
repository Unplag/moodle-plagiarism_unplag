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
 * unplag_api.class.php - SDK for working with unplag api.
 *
 * @package     plagiarism_unplag
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\task;

use core\task\adhoc_task;
use plagiarism_unplag\classes\plagiarism\unplag_content;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;

/**
 * Class unplag_upload_and_check_task
 *
 * @package plagiarism_unplag\classes\task
 * @namespace plagiarism_unplag\classes\task
 *
 */
class unplag_upload_and_check_task extends adhoc_task {

    public function execute() {
        $data = $this->get_custom_data();
        if (file_exists($data->tmpfile)) {
            $content = file_get_contents($data->tmpfile);
            $ucore = new unplag_core($data->unplagcore->cmid, $data->unplagcore->userid);
            $plagiarismentity = new unplag_content($ucore, $content, $data->filename, $data->format, $data->parent_id);
            unset($content, $ucore);
            @unlink($data->tmpfile);
            $internalfile = $plagiarismentity->upload_file_on_unplag_server();

            if (isset($internalfile->check_id)) {
                mtrace('File with uuid' . $internalfile->identifier . ' already sent to Unplag');
            } elseif ($internalfile->external_file_id) {
                $checkresp = unplag_api::instance()->run_check($internalfile);
                $plagiarismentity->handle_check_response($checkresp);
                mtrace('file ' . $internalfile->identifier . 'send to Unplag');
            }

            unset($internalfile, $plagiarismentity, $checkresp);
        } else {
            mtrace('file ' . $data->tmpfile . 'not exist');
        }
    }
}