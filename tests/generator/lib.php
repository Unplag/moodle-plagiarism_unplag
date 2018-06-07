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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;

require_once($CFG->dirroot . '/plagiarism/unplag/classes/unplag_core.class.php');

use plagiarism_unplag\classes\unplag_core;

/**
 * Class plagiarism_unplag_generator
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_unplag_generator extends testing_data_generator {

    /**
     * Create plagiarism core
     *
     * @param int $cmid
     * @param int $userid
     * @return unplag_core
     */
    public function create_ucore($cmid, $userid) {
        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);

        return new unplag_core($cmid, $userid, $cm->modname);
    }

    /**
     * Create stored file from fixture
     *
     * @param string $filepath
     * @param object $owner
     * @return stored_file
     */
    public function create_file_from_pathname($filepath, $owner) {
        $syscontext = context_system::instance();
        $filerecord = [
            'contextid' => $syscontext->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => basename($filepath),
            'userid'    => $owner->id
        ];

        $fs = get_file_storage();

        return $fs->create_file_from_pathname($filerecord, $filepath);
    }
}
