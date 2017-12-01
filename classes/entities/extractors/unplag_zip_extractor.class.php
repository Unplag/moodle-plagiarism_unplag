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
 * unplag_zip_extractor.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\entities\extractors;

use plagiarism_unplag\classes\entities\unplag_archive;
use plagiarism_unplag\classes\exception\unplag_exception;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_zip_extractor
 *
 * @package     plagiarism_unplag
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_zip_extractor implements unplag_extractor_interface {
    /**
     * @var \stored_file
     */
    private $file;
    /**
     * @var string
     */
    private $tmpzipfile;
    /**
     * @var \zip_archive
     */
    private $ziparch;

    /**
     * unicheck_zip_extractor constructor.
     *
     * @param \stored_file $file
     *
     * @throws unplag_exception
     */
    public function __construct(\stored_file $file) {
        global $CFG;

        $this->file = $file;

        $this->tmpzipfile = tempnam($CFG->tempdir, 'unicheck_zip');

        $this->file->copy_content_to($this->tmpzipfile);

        $this->ziparch = new \zip_archive();

        if (!$this->ziparch->open($this->tmpzipfile, \file_archive::OPEN)) {
            throw new unplag_exception(unplag_exception::ARCHIVE_CANT_BE_OPEN);
        }
    }

    /**
     * Extract each file
     *
     * @return array
     * @throws unplag_exception
     */
    public function extract() {
        global $CFG;

        if ($this->ziparch->count() == 0) {
            throw new unplag_exception(unplag_exception::ARCHIVE_IS_EMPTY);
        }

        $extracted = [];
        foreach ($this->ziparch as $file) {
            if ($file->is_directory) {
                continue;
            }

            $tmpfile = tempnam($CFG->tempdir, 'unicheck_unzip');

            if (!$fp = fopen($tmpfile, 'wb')) {
                unplag_archive::unlink($tmpfile);
                continue;
            }

            if (!$fz = $this->ziparch->get_stream($file->index)) {
                unplag_archive::unlink($tmpfile);
                continue;
            }

            $bytescopied = stream_copy_to_stream($fz, $fp);

            fclose($fz);
            fclose($fp);

            if ($bytescopied != $file->size) {
                unplag_archive::unlink($tmpfile);
                continue;
            }

            $name = fix_utf8($file->pathname);
            $format = pathinfo($name, PATHINFO_EXTENSION);
            if (!\plagiarism_unplag::is_supported_extension($format)) {
                unplag_archive::unlink($tmpfile);
                continue;
            }

            $extracted[] = [
                'path'     => $tmpfile,
                'filename' => $name,
                'format'   => $format,
            ];
        }

        return $extracted;
    }

    /**
     * Destruct
     */
    public function __destruct() {
        $this->ziparch->close();
        unplag_archive::unlink($this->tmpzipfile);
    }
}
