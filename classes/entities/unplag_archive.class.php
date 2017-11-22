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

use plagiarism_unplag\classes\exception\unplag_exception;
use plagiarism_unplag\classes\plagiarism\unplag_content;
use plagiarism_unplag\classes\task\unplag_upload_and_check_task;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_notification;
use plagiarism_unplag\classes\unplag_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

if (!defined('ARCHIVE_IS_EMPTY')) {
    define('ARCHIVE_IS_EMPTY', 'Archive is empty or contains document(s) with no text');
}

if (!defined('ARCHIVE_CANT_BE_OPEN')) {
    define('ARCHIVE_CANT_BE_OPEN', 'Can\'t open zip archive');
}

/**
 * Class unplag_archive
 *
 * @package   plagiarism_unplag\classes\entities
 * @namespace plagiarism_unplag\classes\entities
 *
 */
class unplag_archive {

    const DEFAULT_SUPPORTED_FILES_COUNT = 10;
    const MIN_SUPPORTED_FILES_COUNT = 1;
    const MAX_SUPPORTED_FILES_COUNT = 100;

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
     * @param unplag_core  $core
     *
     * @throws unplag_exception
     */
    public function __construct(\stored_file $file, unplag_core $core) {
        $this->file = $file;
        $this->unplagcore = $core;
    }

    /**
     * @return bool
     */
    public function run_checks() {
        global $DB;
        global $CFG;

        $archiveinternalfile = $this->unplagcore->get_plagiarism_entity($this->file)->get_internal_file();

        $ziparch = new \zip_archive();

        $tmpzipfile = tempnam($CFG->tempdir, 'unicheck_zip');
        $this->file->copy_content_to($tmpzipfile);
        if (!$ziparch->open($tmpzipfile, \file_archive::OPEN)) {
            $this->invalid_response($archiveinternalfile, ARCHIVE_CANT_BE_OPEN);

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
            $this->invalid_response($archiveinternalfile, ARCHIVE_IS_EMPTY);

            return false;
        }

        try {
            $maxsupportedcount = unplag_settings::get_assign_settings(
                $this->unplagcore->cmid,
                unplag_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT
            );

            if ($maxsupportedcount < self::MIN_SUPPORTED_FILES_COUNT || $maxsupportedcount > self::MAX_SUPPORTED_FILES_COUNT) {
                $maxsupportedcount = self::DEFAULT_SUPPORTED_FILES_COUNT;
            }

            $supportedcount = $this->process_archive_files($ziparch, $archiveinternalfile->id, $maxsupportedcount);
            if ($supportedcount < 1) {
                $this->invalid_response($archiveinternalfile, ARCHIVE_IS_EMPTY);

                return false;
            }
        } catch (\Exception $e) {
            mtrace('Archive error ' . $e->getMessage());
            $this->invalid_response($archiveinternalfile, ARCHIVE_IS_EMPTY);

            return false;
        }

        $archiveinternalfile->statuscode = UNPLAG_STATUSCODE_ACCEPTED;
        $archiveinternalfile->errorresponse = null;

        $DB->update_record(UNPLAG_FILES_TABLE, $archiveinternalfile);

        $ziparch->close();

        return true;
    }

    /**
     * @param \zip_archive $ziparch
     * @param null         $parentid
     * @param int          $maxsupportedcount Max supported processed files
     *
     * @return int
     */
    private function process_archive_files(\zip_archive &$ziparch, $parentid = null, $maxsupportedcount = 10) {
        global $CFG;

        $processed = [];
        $supportedcount = 0;
        foreach ($ziparch as $file) {
            if ($file->is_directory) {
                continue;
            }

            $name = fix_utf8($file->pathname);
            $tmpfile = tempnam($CFG->tempdir, 'unplag_unzip');

            if (!$fp = fopen($tmpfile, 'wb')) {
                $this->unlink($tmpfile);
                $processed[$name] = 'Can not write temp file';
                continue;
            }

            if ($name === '' or array_key_exists($name, $processed)) {
                $this->unlink($tmpfile);
                continue;
            }

            if (!$fz = $ziparch->get_stream($file->index)) {
                $this->unlink($tmpfile);
                $processed[$name] = 'Can not read file from zip archive';
                continue;
            }

            $bytescopied = stream_copy_to_stream($fz, $fp);

            fclose($fz);
            fclose($fp);

            if ($bytescopied != $file->size) {
                $this->unlink($tmpfile);
                $processed[$name] = 'Can not read file from zip archive';
                continue;
            }

            $format = pathinfo($name, PATHINFO_EXTENSION);
            if (!\plagiarism_unplag::is_supported_extension($format)) {
                continue;
            }

            if ($supportedcount >= $maxsupportedcount) {
                break;
            }

            $plagiarismentity = new unplag_content($this->unplagcore, null, $name, $format, $parentid);
            $plagiarismentity->get_internal_file();

            unplag_upload_and_check_task::add_task([
                'tmpfile'    => $tmpfile,
                'filename'   => $name,
                'unplagcore' => $this->unplagcore,
                'format'     => $format,
                'parent_id'  => $parentid,
            ]);

            $supportedcount++;
        }

        return $supportedcount;
    }

    public function restart_check() {
        global $DB;

        $internalfile = $this->unplagcore->get_plagiarism_entity($this->file)->get_internal_file();
        $childs = $DB->get_records_list(UNPLAG_FILES_TABLE, 'parent_id', [$internalfile->id]);
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

    /**
     * @param $file
     */
    private function unlink($file) {
        if (!unlink($file)) {
            mtrace('Error deleting ' . $file);
        }
    }

    /**
     * @param \stdClass $archivefile
     * @param string    $reason
     */
    private function invalid_response($archivefile, $reason) {
        global $DB;

        $archivefile->statuscode = UNPLAG_STATUSCODE_INVALID_RESPONSE;
        $archivefile->errorresponse = json_encode([
            ["message" => $reason],
        ]);

        $DB->update_record(UNPLAG_FILES_TABLE, $archivefile);
    }
}
