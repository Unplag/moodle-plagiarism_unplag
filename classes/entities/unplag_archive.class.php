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
 * unplag_plagiarism_entity.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\entities;

use core\task\manager;
use plagiarism_unplag\classes\exception\unplag_exception;
use plagiarism_unplag\classes\helpers\unplag_stored_file;
use plagiarism_unplag\classes\plagiarism\unplag_content;
use plagiarism_unplag\classes\task\unplag_upload_and_check_task;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_notification;

/**
 * Class unplag_archive
 *
 * @package plagiarism_unplag\classes\entities
 * @namespace plagiarism_unplag\classes\entities
 *
 */
class unplag_archive {

    /**
     * @var \stored_file
     */
    private $file;

    /**
     * @var unplag_core
     */
    private $unplagcore;

    /**
     * unplag_archive constructor.
     *
     * @param \stored_file $file
     * @param unplag_core $core
     * @throws unplag_exception
     */
    public function __construct(\stored_file $file, unplag_core $core) {
        if (!\plagiarism_unplag::is_archive($file)) {
            throw new unplag_exception('File must be archive');
        }

        $this->file = $file;
        $this->unplagcore = $core;
    }

    /**
     * @return bool
     * @todo recursive file extractor
     */
    public function run_checks() {
        global $DB, $CFG;

        $archiveinternalfile = $this->unplagcore->get_plagiarism_entity($this->file)->get_internal_file();

        $ziparch = new \zip_archive();
        $pathname = unplag_stored_file::get_protected_pathname($this->file);
        if (!$ziparch->open($pathname, \file_archive::OPEN)) {
            $archiveinternalfile->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
            $archiveinternalfile->errorresponse = json_encode(array(
                    array("message" => "Can't open zip archive")
            ));

            $DB->update_record(UNPLAG_FILES_TABLE, $archiveinternalfile);
            return false;
        }

        $fileexist = false;
        foreach ($ziparch as $file) {
            if (!$file->is_directory) {
                $fileexist = true;
                break;
            }
        }

        if (!$fileexist) {
            $archiveinternalfile->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
            $archiveinternalfile->errorresponse = json_encode(array(
                    array("message" => "Empty archive")
            ));
            $DB->update_record(UNPLAG_FILES_TABLE, $archiveinternalfile);
            return false;
        }

        $processed = array();
        foreach ($ziparch as $file) {
            if ($file->is_directory) {
                continue;
            }

            $name = $file->pathname;
            $format = pathinfo($name, PATHINFO_EXTENSION);

            $content = '';
            $tmpfile = tempnam($CFG->tempdir . '/zip', 'unzip');

            if (!$fp = fopen($tmpfile, 'wb')) {
                @unlink($tmpfile);
                $processed[$name] = 'Can not write temp file';
                continue;
            }

            if ($name === '' or array_key_exists($name, $processed)) {
                @unlink($tmpfile);
                continue;
            }

            if (!$fz = $ziparch->get_stream($file->index)) {
                @unlink($tmpfile);
                $processed[$name] = 'Can not read file from zip archive';
                continue;
            }

            while (!feof($fz)) {
                $content .= fread($fz, 262143);
                fwrite($fp, $content);
            }
            fclose($fz);
            fclose($fp);

            $plagiarismentity = new unplag_content($this->unplagcore, $content, $name, $format, $archiveinternalfile->id);
            $plagiarismentity->get_internal_file();

            $task = new unplag_upload_and_check_task();
            $task->set_custom_data(array(
                    'tmpfile' => $tmpfile,
                    'filename' => $name,
                    'unplagcore' => $this->unplagcore,
                    'format' => $format,
                    'parent_id' => $archiveinternalfile->id
            ));
            $task->set_component('unplag');

            manager::queue_adhoc_task($task);

            unset($content);
        }

        $archiveinternalfile->statuscode = UNPLAG_STATUSCODE_ACCEPTED;
        $archiveinternalfile->errorresponse = null;

        $DB->update_record(UNPLAG_FILES_TABLE, $archiveinternalfile);

        $ziparch->close();

        return true;
    }

    public function restart_check() {
        global $DB;

        $internalfile = $this->unplagcore->get_plagiarism_entity($this->file)->get_internal_file();
        $childs = $DB->get_records_list(UNPLAG_FILES_TABLE, 'parent_id', array($internalfile->id));
        if ($childs) {
            foreach ((object) $childs as $child) {
                if ($child->check_id) {
                    unplag_api::instance()->delete_check($child);
                }
            }

            unplag_notification::success('plagiarism_run_success', true);

            $this->run_checks();
        }
    }
}
