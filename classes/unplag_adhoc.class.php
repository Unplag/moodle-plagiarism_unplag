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
 * unplag_adhoc.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes;

use plagiarism_unplag\classes\entities\providers\unplag_file_provider;
use plagiarism_unplag\classes\services\storage\unplag_file_state;
use plagiarism_unplag\classes\task\unplag_check_starter;
use plagiarism_unplag\classes\task\unplag_upload_task;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_adhoc
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_adhoc {

    /**
     * Add task to upload queue
     *
     * @param \stored_file $file
     * @param unplag_core  $ucore
     * @return bool
     */
    public static function upload(\stored_file $file, unplag_core $ucore) {
        $plagiarismfile = $ucore->get_plagiarism_entity($file)->get_internal_file();
        // Check if document file already uploaded.
        if (isset($plagiarismfile->external_file_uuid) && $plagiarismfile->external_file_uuid) {
            return false;
        }

        // Check if archive file already uploaded.
        if ($plagiarismfile->type === unplag_plagiarism_entity::TYPE_ARCHIVE
            && $plagiarismfile->state !== unplag_file_state::CREATED) {
            return false;
        }

        $plagiarismfile->state = unplag_file_state::UPLOADING;
        $plagiarismfile->errorresponse = null;

        unplag_file_provider::save($plagiarismfile);

        return unplag_upload_task::add_task([
            unplag_upload_task::PATHNAME_HASH => $file->get_pathnamehash(),
            unplag_upload_task::UCORE_KEY     => $ucore,
        ]);
    }

    /**
     * Add task to check queue
     *
     * @param \stdClass $plagiarismfile
     * @return bool
     */
    public static function check(\stdClass $plagiarismfile) {
        $plagiarismfile->state = unplag_file_state::CHECKING;
        $plagiarismfile->errorresponse = null;
        unplag_file_provider::save($plagiarismfile);

        return unplag_check_starter::add_task([
            unplag_check_starter::PLUGIN_FILE_ID_KEY => $plagiarismfile->id
        ]);
    }
}