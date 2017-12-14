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
 * plagiarism_unplag_basic_testcase.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag_unittests;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_api_fixture
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_api_fixture {
    /**
     * Upload file
     *
     * @param string|resource $content
     * @param string          $filename
     * @param string          $format
     * @param integer         $cmid
     * @param object|null     $owner
     * @param \stdClass       $internalfile
     *
     * @return \stdClass
     */
    public function upload_file(&$content, $filename, $format = 'html', $cmid, $owner = null, $internalfile) {

        return (object)[
            "result"         => true,
            "errors"         => [],
            "file"           => (object)[
                "id"                => null,
                'uuid'              => '7d812e4747b549a4be9807e16f975f25',
                'name'              => $filename,
                'size'              => '166175',
                'pages_count'       => 0,
                'words_count'       => 0,
                'print_pages_count' => 0,
                'format'            => $format,
                'checks'            => [],
                'directory_id'      => '5741'
            ],
            'tracking_token' => '7d812e4747b549a4be9807e16f975f25'
        ];
    }
}