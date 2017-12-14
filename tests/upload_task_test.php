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
 * plagiarism_unplag_upload_task_testcase.php
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

require_once($CFG->dirroot . '/plagiarism/unplag/tests/advanced_test.php');
require_once($CFG->dirroot . '/plagiarism/unplag/classes/task/unplag_abstract_task.class.php');
require_once($CFG->dirroot . '/plagiarism/unplag/classes/task/unplag_upload_task.class.php');
require_once($CFG->dirroot . '/plagiarism/unplag/classes/services/storage/unplag_file_state.class.php');
require_once($CFG->dirroot . '/plagiarism/unplag/classes/entities/providers/unplag_file_provider.class.php');

use plagiarism_unplag\classes\entities\providers\unplag_file_provider;
use plagiarism_unplag\classes\services\storage\unplag_file_state;
use plagiarism_unplag\classes\task\unplag_upload_task;

/**
 * Class plagiarism_unplag_upload_task_testcase
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_unplag_upload_task_testcase extends plagiarism_unplag_advanced_testcase {

    /**
     * test_execute
     *
     * @param string $filepath
     *
     * @dataProvider filepath_provider
     */
    public function test_execute($filepath) {
        $generator = $this->getDataGenerator()->get_plugin_generator('plagiarism_unplag');
        $ucore = $generator->create_ucore($this->assign->get_course_module()->id, $this->students[0]->id);
        /** @var stored_file $file */
        $file = $generator->create_file_from_pathname($filepath, $this->students[0]);

        $plagiarismfile = $ucore->get_plagiarism_entity($file)->get_internal_file();

        $this->assertEquals('CREATED', $plagiarismfile->state);

        $plagiarismfile->state = unplag_file_state::UPLOADING;

        $this->assertEquals('UPLOADING', $plagiarismfile->state);

        unplag_file_provider::save($plagiarismfile);

        $task = new unplag_upload_task();
        $task->set_custom_data([
            unplag_upload_task::PATHNAME_HASH => $file->get_pathnamehash(),
            unplag_upload_task::UCORE_KEY     => $ucore,
        ]);

        $task->execute();

        $plagiarismfile = $ucore->get_plagiarism_entity($file)->get_internal_file();

        $this->assertEquals($ucore->cmid, $plagiarismfile->cm);
        $this->assertEquals($this->students[0]->id, $plagiarismfile->userid);
        $this->assertEquals(0, $plagiarismfile->progress);
        $this->assertEquals(0.00, $plagiarismfile->similarityscore);
        $this->assertEquals(basename($filepath), $plagiarismfile->filename);
        $this->assertEquals('UPLOADING', $plagiarismfile->state);

        if ($plagiarismfile->type == 'archive') {
            $childfiles = unplag_file_provider::get_file_list_by_parent_id($plagiarismfile->id);
            $this->assertEquals(0, $plagiarismfile->attempt);
            foreach ($childfiles as $childfile) {
                $this->assertEquals($ucore->cmid, $childfile->cm);
                $this->assertEquals($plagiarismfile->id, $childfile->parent_id);
                $this->assertEquals('document', $childfile->type);
                $this->assertEquals(0, $childfile->progress);
                $this->assertEquals(0.00, $childfile->similarityscore);
                $this->assertEquals(1, $childfile->attempt);
                $this->assertEquals('UPLOADING', $childfile->state);
                $this->assertEquals('7d812e4747b549a4be9807e16f975f25', $childfile->external_file_uuid);
            }
        } else {
            $this->assertNull($plagiarismfile->parent_id);
            $this->assertEquals(1, $plagiarismfile->attempt);
            $this->assertEquals('7d812e4747b549a4be9807e16f975f25', $plagiarismfile->external_file_uuid);
        }
    }

    /**
     * Data provider for real filepath
     *
     * @return array
     */
    public function filepath_provider() {
        global $CFG;

        return [
            [$CFG->dirroot . '/plagiarism/unplag/tests/fixtures/sample.pdf'],
            [$CFG->dirroot . '/plagiarism/unplag/tests/fixtures/Doc.doc'],
            [$CFG->dirroot . '/plagiarism/unplag/tests/fixtures/Docx.docx'],
            [$CFG->dirroot . '/plagiarism/unplag/tests/fixtures/Html.htm'],
            [$CFG->dirroot . '/plagiarism/unplag/tests/fixtures/Odt.odt'],
            [$CFG->dirroot . '/plagiarism/unplag/tests/fixtures/Rtf.rtf'],
            [$CFG->dirroot . '/plagiarism/unplag/tests/fixtures/Zip.zip']
        ];
    }
}