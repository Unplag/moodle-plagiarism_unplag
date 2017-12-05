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
 * unplag_bulk_check_assign_files.class.php
 *
 * @package     plagiarism_unplag
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\task;

use plagiarism_unplag\classes\entities\unplag_archive;
use plagiarism_unplag\classes\services\storage\unplag_file_state;
use plagiarism_unplag\classes\unplag_adhoc;
use plagiarism_unplag\classes\unplag_assign;
use plagiarism_unplag\classes\unplag_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_bulk_check_assign_files
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_bulk_check_assign_files extends unplag_abstract_task {
    /** @var  unplag_core */
    private $ucore;
    /** @var  \stored_file */
    private $assignfile;

    /**
     * Execute of adhoc task
     */
    public function execute() {
        $data = $this->get_custom_data();

        $assignfiles = unplag_assign::get_area_files($data->contextid);
        if (empty($assignfiles)) {
            return;
        }

        foreach ($assignfiles as $this->assignfile) {
            if (unplag_assign::is_draft($this->assignfile->get_itemid())) {
                continue;
            }

            $this->ucore = new unplag_core($data->cmid, $this->assignfile->get_userid(), $this->get_modname($data));

            $pattern = '%s with uuid ' . $this->assignfile->get_pathnamehash() . ' ready to send';
            if (\plagiarism_unplag::is_archive($this->assignfile)) {
                mtrace(sprintf($pattern, 'Archive'));
                $this->handle_archive($data->cmid);
            } else {
                mtrace(sprintf($pattern, 'File'));
                $this->handle_non_archive();
            }
        }
    }

    /**
     * Process archives
     *
     * @param int $contextid
     */
    private function handle_archive($contextid) {
        if (!is_null(unplag_core::get_file_by_hash($contextid, $this->assignfile->get_pathnamehash()))) {
            return;
        }

        (new unplag_archive($this->assignfile, $this->ucore))->upload();
        mtrace('... archive send to Unicheck');
    }

    /**
     * Process files besides archives
     */
    private function handle_non_archive() {
        $plagiarismentity = $this->ucore->get_plagiarism_entity($this->assignfile);
        $internalfile = $plagiarismentity->upload_file_on_unplag_server();
        if (!$internalfile) {
            mtrace("... Can't process stored file {$this->assignfile->get_id()}");

            return;
        }

        if (isset($internalfile->check_id)) {
            return;
        }

        switch ($internalfile->state) {
            case unplag_file_state::CREATED:
                unplag_adhoc::upload($this->assignfile, $this->ucore);
                mtrace('... file start uploading in Unicheck');
                break;
            case unplag_file_state::UPLOADED:
                unplag_adhoc::check($internalfile);
                mtrace('... file start checking in Unicheck');
                break;
        }
    }
}