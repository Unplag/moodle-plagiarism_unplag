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
 * unplag_archive.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\entities;

use plagiarism_unplag\classes\entities\extractors\unplag_extractor_interface;
use plagiarism_unplag\classes\entities\extractors\unplag_zip_extractor;
use plagiarism_unplag\classes\entities\providers\unplag_file_provider;
use plagiarism_unplag\classes\exception\unplag_exception;
use plagiarism_unplag\classes\unplag_adhoc;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_notification;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_archive
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_archive {

    /**
     * DEFAULT_SUPPORTED_FILES_COUNT
     */
    const DEFAULT_SUPPORTED_FILES_COUNT = 10;
    /**
     * MIN_SUPPORTED_FILES_COUNT
     */
    const MIN_SUPPORTED_FILES_COUNT = 1;
    /**
     * MAX_SUPPORTED_FILES_COUNT
     */
    const MAX_SUPPORTED_FILES_COUNT = 100;

    /**
     * ZIP_MIMETYPE
     */
    const ZIP_MIMETYPE = 'application/zip';

    /**
     * @var \stored_file
     */
    private $file;
    /**
     * @var unplag_core
     */
    private $core;

    /**
     * @var unplag_extractor_interface
     */
    private $extractor;

    /**
     * @var object
     */
    private $archive;

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
        $this->core = $core;

        $this->archive = $this->core->get_plagiarism_entity($this->file)->get_internal_file();

        switch ($file->get_mimetype()) {
            case self::ZIP_MIMETYPE:
                $this->extractor = new unplag_zip_extractor($file);
                break;
            default:
                throw new unplag_exception(unplag_exception::UNSUPPORTED_MIMETYPE);
        }
    }

    /**
     * Extract each file
     *
     * @return array
     *
     * @throws unplag_exception
     */
    public function extract() {
        try {
            return $this->extractor->extract();
        } catch (\Exception $ex) {
            throw new unplag_exception($ex->getMessage());
        }
    }

    /**
     * Upload archive for check
     *
     * @return bool
     */
    public function upload() {
        return unplag_adhoc::upload($this->file, $this->core);
    }

    /**
     * Restart check
     */
    public function restart_check() {
        $internalfile = $this->core->get_plagiarism_entity($this->file)->get_internal_file();
        $childs = unplag_file_provider::get_file_list_by_parent_id($internalfile->id);
        if (count($childs)) {
            foreach ((object)$childs as $child) {
                if ($child->check_id) {
                    unplag_api::instance()->delete_check($child);
                }
            }

            unplag_notification::success('plagiarism_run_success', true);

            $this->upload();
        }
    }

    /**
     * Delete
     *
     * @param string $file
     */
    public static function unlink($file) {
        if (!unlink($file)) {
            mtrace('Error deleting ' . $file);
        }
    }
}
