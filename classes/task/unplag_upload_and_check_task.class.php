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
 * unplag_upload_and_check_task.class.php
 *
 * @package     plagiarism_unplag
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\task;

use plagiarism_unplag\classes\helpers\unplag_check_helper;
use plagiarism_unplag\classes\plagiarism\unplag_content;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_assign;
use plagiarism_unplag\classes\unplag_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_upload_and_check_task
 *
 * @package   plagiarism_unplag\classes\task
 * @namespace plagiarism_unplag\classes\task
 *
 */
class unplag_upload_and_check_task extends unplag_abstract_task {
    public function execute() {
        $data = $this->get_custom_data();
        if (file_exists($data->tmpfile)) {
            $ucore = new unplag_core($data->unplagcore->cmid, $data->unplagcore->userid);

            if ((bool) unplag_assign::get_by_cmid($ucore->cmid)->teamsubmission) {
                $ucore->enable_teamsubmission();
            }

            $content = file_get_contents($data->tmpfile);
            $plagiarismentity = new unplag_content($ucore, $content, $data->filename, $data->format, $data->parent_id);

            unset($content, $ucore);

            if (!unlink($data->tmpfile)) {
                mtrace('Error deleting ' . $data->tmpfile);
            }

            unplag_check_helper::upload_and_run_detection($plagiarismentity);

            unset($internalfile, $plagiarismentity, $checkresp);
        } else {
            mtrace('file ' . $data->tmpfile . 'not exist');
        }
    }
}