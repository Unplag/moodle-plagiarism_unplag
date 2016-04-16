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
namespace plagiarism_unplag\classes\event;

use core\event\base;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_plagiarism_entity;

/**
 * Class unplag_abstract_event
 * @package plagiarism_unplag\classes\event
 */
abstract class unplag_abstract_event {
    /**
     * @param                          $internalfile
     * @param unplag_plagiarism_entity $plagiarismentity
     */
    protected static function after_hanle_event($internalfile, unplag_plagiarism_entity $plagiarismentity) {
        if (isset($internalfile->external_file_id)) {
            $checkresp = unplag_api::instance()->run_check($internalfile);
            if ($checkresp->result === true) {
                $plagiarismentity->update_file_accepted($checkresp->check);
            }
        }
    }

    /**
     * @param unplag_core $unplagcore
     * @param base        $event
     *
     * @return mixed
     */
    abstract public function handle_event(unplag_core $unplagcore, base $event);
}