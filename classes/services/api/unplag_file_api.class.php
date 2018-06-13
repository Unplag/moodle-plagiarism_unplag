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
 * unplag_file_api.class.php
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
 * Class unplag_file_api
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_file_api {

    const FOR_UPDATE = 'check_to_update';
    const FOR_CREATE = 'check_to_create';

    /**
     * @param $dbfiles
     *
     * @return array
     */
    public function get_uploaded_file_by_dbfiles($dbfiles) {
        $fileforupdatelist = [
            self::FOR_UPDATE => [],
            self::FOR_CREATE => []
        ];
        $apirequest = new unplag_api();
        foreach ($dbfiles as $file) {
            $resultfileprogress = $apirequest->get_file_upload_progress($file->external_file_uuid);
            if ($resultfileprogress->result && $resultfileprogress->progress->percentage == 100) {
                $externalfile = $apirequest->get_file_info($file->external_file_id);
                if ($externalfile->result && $externalfile->file && $externalfile->file->checks) {
                    $fileforupdatelist[self::FOR_UPDATE][$file->id] = $externalfile->file->checks[0];
                } elseif ($externalfile->result && $externalfile->file) {
                    $fileforupdatelist[self::FOR_CREATE][$file->id] = $externalfile->file;
                }
            }
        }

        return $fileforupdatelist;
    }
}