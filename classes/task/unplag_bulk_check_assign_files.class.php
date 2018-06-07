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

    /**
     * Execute of adhoc task
     */
    public function execute() {
        $data = $this->get_custom_data();

        $storedfiles = unplag_assign::get_area_files($data->contextid);
        if (empty($storedfiles)) {
            return;
        }

        foreach ($storedfiles as $storedfile) {
            if (unplag_assign::is_draft($storedfile->get_itemid())) {
                continue;
            }

            $this->ucore = new unplag_core($data->cmid, $storedfile->get_userid(), $this->get_modname($data));
            $plagiarismfile = $this->get_plagiarism_file($storedfile);
            if (!$plagiarismfile || $plagiarismfile->state !== unplag_file_state::CREATED) {
                continue;
            }

            if (\plagiarism_unplag::is_archive($storedfile)) {
                (new unplag_archive($storedfile, $this->ucore))->upload();
            } else {
                unplag_adhoc::upload($storedfile, $this->ucore);
            }
        }
    }

    /**
     * Get plagiarism file
     *
     * @param \stored_file $storedfile
     * @return null|object
     */
    private function get_plagiarism_file(\stored_file $storedfile) {
        $plagiarismentity = $this->ucore->get_plagiarism_entity($storedfile);
        $plagiarismfile = $plagiarismentity->get_internal_file();
        if (!$plagiarismfile) {
            mtrace("... Can't process stored file {$storedfile->get_id()}");

            return null;
        }

        return $plagiarismfile;
    }
}