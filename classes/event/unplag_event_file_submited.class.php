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
 * unplag_event_file_submited.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\event;

use core\event\base;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_plagiarism_entity;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_event_file_submited
 *
 * @package plagiarism_unplag\classes\event
 */
class unplag_event_file_submited extends unplag_abstract_event {
    /**
     * @param unplag_core $unplagcore
     * @param base        $event
     */
    public function handle_event(unplag_core $unplagcore, base $event) {
        if (self::is_submition_draft($event) ||
            !isset($event->other['pathnamehashes']) || empty($event->other['pathnamehashes'])
        ) {
            return;
        }

        $this->unplagcore = $unplagcore;

        foreach ($event->other['pathnamehashes'] as $pathnamehash) {
            $this->add_after_handle_task($this->handle_uploaded_file($pathnamehash));
        }

        $this->after_handle_event();
    }

    /**
     * @param $pathnamehash
     *
     * @return null|unplag_plagiarism_entity
     */
    private function handle_uploaded_file($pathnamehash) {
        $file = get_file_storage()->get_file_by_hash($pathnamehash);
        if ($file->is_directory()) {
            return null;
        }
        $plagiarismentity = $this->unplagcore->get_plagiarism_entity($file);
        $plagiarismentity->upload_file_on_unplag_server();

        return $plagiarismentity;
    }
}