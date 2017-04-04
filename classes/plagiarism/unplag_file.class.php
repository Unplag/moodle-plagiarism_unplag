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

namespace plagiarism_unplag\classes\plagiarism;

use plagiarism_unplag\classes\exception\unplag_exception;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_plagiarism_entity;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_file
 *
 * @package   plagiarism_unplag\classes\plagiarism
 * @namespace plagiarism_unplag\classes\plagiarism
 *
 */
class unplag_file extends unplag_plagiarism_entity {
    /**
     * @var \stored_file
     */
    private $file;

    /**
     * @var array
     */
    private $mimetypes = [
            'application/pdf'                                                           => 'pdf',
            'application/vnd.oasis.opendocument.text'                                   => 'odt',
            'application/vnd.oasis.opendocument.presentation'                           => 'odp',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'text/html'                                                                 => 'html',
            'text/plain'                                                                => 'txt',
            'text/rtf'                                                                  => 'rtf',
            'text/x-rtf'                                                                => 'rtf',
            'text/richtext'                                                             => 'rtf',
            'text/mspowerpoint'                                                         => 'ppt',
            'text/powerpoint'                                                           => 'ppt',
            'text/vnd.ms-powerpoint'                                                    => 'ppt',
            'text/x-mspowerpoint'                                                       => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-iwork-pages-sffpages'                                        => 'pages'
    ];

    /**
     * unplag_file constructor.
     *
     * @param unplag_core  $core
     * @param \stored_file $file
     *
     * @throws unplag_exception
     */
    public function __construct(unplag_core $core, \stored_file $file) {
        if (!$file) {
            throw new unplag_exception('Invalid argument file');
        }

        $this->core = $core;
        $this->file = $file;
    }

    /**
     * @return object
     */
    public function get_internal_file() {
        global $DB;

        if ($this->plagiarismfile) {
            return $this->plagiarismfile;
        }

        $plagiarismfile = null;
        try {
            $filedata = [
                    'cm'         => $this->cmid(),
                    'userid'     => $this->userid(),
                    'identifier' => $this->stored_file()->get_pathnamehash(),
            ];

            if ($this->core->is_teamsubmission_mode()) {
                unset($filedata['userid']);
            }

            // Now update or insert record into unplag_files.
            $plagiarismfile = $DB->get_record(UNPLAG_FILES_TABLE, $filedata);

            if (empty($plagiarismfile)) {
                $plagiarismfile = $this->new_plagiarismfile([
                        'cm'         => $this->cmid(),
                        'userid'     => $this->userid(),
                        'identifier' => $this->stored_file()->get_pathnamehash(),
                        'filename'   => $this->stored_file()->get_filename(),
                ]);

                if (\plagiarism_unplag::is_archive($this->stored_file())) {
                    $plagiarismfile->type = unplag_plagiarism_entity::TYPE_ARCHIVE;
                }

                if (!$pid = $DB->insert_record(UNPLAG_FILES_TABLE, $plagiarismfile)) {
                    debugging("INSERT INTO {UNPLAG_FILES_TABLE}");
                }

                $plagiarismfile->id = $pid;
            }
        } catch (\Exception $ex) {
            debugging("get internal file error: {$ex->getMessage()}");
        }

        $this->plagiarismfile = $plagiarismfile;

        return $this->plagiarismfile;
    }

    /**
     * @return \stored_file
     */
    public function stored_file() {
        return $this->file;
    }

    /**
     * @param $mimetype
     * @return string|null
     */
    public function mimetype_to_format($mimetype) {
        return isset($this->mimetypes[$mimetype]) ? $this->mimetypes[$mimetype] : null;
    }

    /**
     * @return array
     */
    protected function build_upload_data() {
        $format = 'html';
        $source = $this->stored_file()->get_source();
        $mimetype = $this->stored_file()->get_mimetype();

        if ($source) {
            $format = pathinfo($source, PATHINFO_EXTENSION);
        }

        if (!$format && $mimetype) {
            $format = $this->mimetype_to_format($mimetype);

            if (!$format) {
                debugging("Can't detect file format.
                    Filename: {$this->stored_file()->get_filename()}, mimetype: {$this->stored_file()->get_mimetype()}"
                );
            }
        }

        return [
                $this->stored_file()->get_content_file_handle(),
                $this->stored_file()->get_filename(),
                $format,
                $this->cmid(),
                unplag_core::get_user($this->stored_file()->get_userid()),
        ];
    }
}
