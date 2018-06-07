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
require_once($CFG->dirroot . '/plagiarism/unplag/classes/services/storage/unplag_file_state.class.php');
require_once($CFG->dirroot . '/plagiarism/unplag/classes/unplag_api.class.php');
require_once($CFG->dirroot . '/plagiarism/unplag/tests/fixtures/unplag_api_fixture.php');

use plagiarism_unplag_unittests\unplag_api_fixture;

/**
 * Class plagiarism_unplag_basic_testcase
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_unplag_advanced_testcase extends advanced_testcase {

    /** Default number of students to create */
    const DEFAULT_STUDENT_COUNT = 3;
    /** Default number of teachers to create */
    const DEFAULT_TEACHER_COUNT = 2;
    /** Default number of editing teachers to create */
    const DEFAULT_EDITING_TEACHER_COUNT = 2;
    /** Number of groups to create */
    const GROUP_COUNT = 6;

    /** @var stdClass $course New course created to hold the assignments */
    protected $course = null;

    /** @var stdClass $assign New assignment created */
    protected $assign = null;

    /** @var array $teachers List of DEFAULT_TEACHER_COUNT teachers in the course */
    protected $teachers = null;

    /** @var array $editingteachers List of DEFAULT_EDITING_TEACHER_COUNT editing teachers in the course */
    protected $editingteachers = null;

    /** @var array $students List of DEFAULT_STUDENT_COUNT students in the course */
    protected $students = null;

    /** @var array $groups List of 10 groups in the course */
    protected $groups = null;

    /** @var array $ucore Plagiarism core */
    protected $ucore = null;

    /**
     * Setup function - we will create a course and add an assign instance to it.
     */
    protected function setUp() {
        global $DB;

        $this->resetAfterTest(true);

        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $assigninstance = $assigngenerator->create_instance(['course' => $this->course->id]);

        $cm = get_coursemodule_from_instance('assign', $assigninstance->id);
        $context = context_module::instance($cm->id);
        $this->assign = new assign($context, $cm, $this->course);

        $this->teachers = [];
        for ($i = 0; $i < self::DEFAULT_TEACHER_COUNT; $i++) {
            array_push($this->teachers, $this->getDataGenerator()->create_user());
        }

        $this->editingteachers = [];
        for ($i = 0; $i < self::DEFAULT_EDITING_TEACHER_COUNT; $i++) {
            array_push($this->editingteachers, $this->getDataGenerator()->create_user());
        }

        $this->students = [];
        for ($i = 0; $i < self::DEFAULT_STUDENT_COUNT; $i++) {
            array_push($this->students, $this->getDataGenerator()->create_user());
        }

        $this->groups = [];
        for ($i = 0; $i < self::GROUP_COUNT; $i++) {
            array_push($this->groups, $this->getDataGenerator()->create_group(['courseid' => $this->course->id]));
        }

        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        foreach ($this->teachers as $i => $teacher) {
            $this->getDataGenerator()->enrol_user($teacher->id,
                $this->course->id,
                $teacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $teacher);
        }

        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        foreach ($this->editingteachers as $i => $editingteacher) {
            $this->getDataGenerator()->enrol_user($editingteacher->id,
                $this->course->id,
                $editingteacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $editingteacher);
        }

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        foreach ($this->students as $i => $student) {
            $this->getDataGenerator()->enrol_user($student->id,
                $this->course->id,
                $studentrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $student);
        }

        $reflection = new \ReflectionProperty('\plagiarism_unplag\classes\unplag_api', 'instance');
        $reflection->setAccessible(true);
        $reflection->setValue(null, new unplag_api_fixture());
    }

    /**
     * test_create_ucore
     */
    public function test_create_ucore() {
        $generator = $this->getDataGenerator()->get_plugin_generator('plagiarism_unplag');

        foreach ($this->students as $student) {
            $ucore = $generator->create_ucore(
                $this->assign->get_course_module()->id,
                $student->id
            );

            $this->assertNotEmpty($ucore);
        }
    }
}
