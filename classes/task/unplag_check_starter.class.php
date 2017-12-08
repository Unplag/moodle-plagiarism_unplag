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
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\task;

use plagiarism_unplag\classes\entities\providers\unplag_file_provider;
use plagiarism_unplag\classes\helpers\unplag_check_helper;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_bulk_check_assign_files
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_check_starter extends unplag_abstract_task {

    /**
     * Key of plugin file id data parameter
     */
    const PLUGIN_FILE_ID_KEY = 'plugin_file_id';
    /**
     * Key of ucore data parameter
     */
    const UCORE_KEY = 'ucore';

    /**
     * @var object
     */
    protected $internalfile;

    /**
     * Execute of adhoc task
     */
    public function execute() {
        $data = $this->get_custom_data();
        if (!is_object($data)) {
            return;
        }

        if (!property_exists($data, self::PLUGIN_FILE_ID_KEY)) {
            return;
        }

        $file = unplag_file_provider::find_by_id($data->plugin_file_id);
        if (!$file) {
            mtrace("File not found. Skipped. Plugin file id: {$data->plugin_file_id}");

            return;
        }

        if ($file->check_id) {
            mtrace("File already checked. Skipped. Plugin file id: {$data->plugin_file_id}");

            return;
        }

        unplag_check_helper::run_plagiarism_detection($file);
    }
}