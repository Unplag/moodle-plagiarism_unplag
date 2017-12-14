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

require_once($CFG->dirroot . '/plagiarism/unplag/classes/services/storage/unplag_file_state.class.php');

/**
 * Class plagiarism_unplag_basic_testcase
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_unplag_basic_testcase extends \basic_testcase {
    /**
     * Test file states exist
     */
    public function test_state_exist() {
        $class = new ReflectionClass('plagiarism_unplag\classes\services\storage\unplag_file_state');
        $states = $class->getConstants();

        $this->assertTrue(in_array('CREATED', $states));
        $this->assertTrue(in_array('UPLOADING', $states));
        $this->assertTrue(in_array('UPLOADED', $states));
        $this->assertTrue(in_array('CHECKING', $states));
        $this->assertTrue(in_array('CHECKED', $states));
        $this->assertTrue(in_array('HAS_ERROR', $states));
    }
}