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
use plagiarism_unplag\classes\unplag_api_request;

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

    /**
     * Handle documents that need to be updated
     */
    const TO_UPDATE = 'check_to_update';

    /**
     * Handle documents that need to be created
     */
    const TO_CREATE = 'check_to_create';

    /**
     * Handle documents that didn't upload throw API
     */
    const TO_ERROR = 'file_to_error';

    /**
     * File info
     */
    const FILE_GET = 'file/get';

    /**
     * Get documents throw API
     *
     * @param array $dbfiles
     *
     * @return array
     */
    public function get_uploaded_file_by_dbfiles($dbfiles) {
        $fileforupdatelist = [
            self::TO_UPDATE => [],
            self::TO_CREATE => [],
            self::TO_ERROR => [],
        ];
        $apirequest = new unplag_api();
        foreach ($dbfiles as $file) {
            $resultfileprogress = $apirequest->get_file_upload_progress($file->external_file_uuid);
            if ($resultfileprogress->result && $resultfileprogress->progress->percentage == 100) {
                $externalfile = $this->get_file_info($resultfileprogress->progress->file->id);
                if ($externalfile->result && $externalfile->file && $externalfile->file->checks) {
                    $fileforupdatelist[self::TO_UPDATE][$file->id] = $externalfile->file->checks[0];
                } else if ($externalfile->result && $externalfile->file) {
                    $fileforupdatelist[self::TO_CREATE][$file->id] = $externalfile->file;
                }
            } else if (!isset($resultfileprogress->progress->file)) {
                $fileforupdatelist[self::TO_ERROR][] = $file;
            }
        }

        return $fileforupdatelist;
    }

    /**
     * Get file info
     *
     * @param int $id
     *
     * @return \stdClass
     */
    public function get_file_info($id) {
        return unplag_api_request::instance()->http_get()->request(self::FILE_GET, [
            'id' => $id
        ]);
    }
}