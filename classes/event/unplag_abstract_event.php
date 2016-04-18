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
 * unplag_abstract_event.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace plagiarism_unplag\classes\event;

use core\event\base;
use plagiarism_unplag;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_core;
use plagiarism_unplag\classes\unplag_plagiarism_entity;

/**
 * Class unplag_abstract_event
 * @package plagiarism_unplag\classes\event
 */
abstract class unplag_abstract_event {
    /** @var */
    protected static $instance;

    /**
     * @return static
     */
    public static function instance() {
        return isset(static::$instance) ? static::$instance : static::$instance = new static;
    }

    /**
     * @param base                     $event
     * @param                          $internalfile
     * @param unplag_plagiarism_entity $plagiarismentity
     *
     * @return null
     */
    protected static function after_hanle_event(base $event, $internalfile, unplag_plagiarism_entity $plagiarismentity) {
        if (plagiarism_unplag::is_submition_draft($event->contextinstanceid)) {
            // Skip this file check cause assign is draft.
            return null;
        }

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
     */
    abstract public function handle_event(unplag_core $unplagcore, base $event);
}