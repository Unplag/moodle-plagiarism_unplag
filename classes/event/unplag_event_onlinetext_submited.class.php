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
 * unplag_event_onlinetext_submited.class.php
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

/**
 * Class unplag_event_onlinetext_submited
 * @package plagiarism_unplag\classes\event
 */
class unplag_event_onlinetext_submited extends unplag_abstract_event {
    /** @var */
    protected static $instance;

    /**
     * @param unplag_core $unplagcore
     * @param base        $event
     */
    public function handle_event(unplag_core $unplagcore, base $event) {
        //global $DB;

        if (empty($event->other['content'])) {
            return;
        }

        //file_put_contents('/tmp/moodle_debug.txt', print_r($event->get_data(), true), FILE_APPEND);
        /*$submission = $DB->get_record(self::handle_object_table($event->objecttable), ['id' => $event->objectid]);

        $content = null;
        switch ($event->objecttable) {
            case 'assignsubmission_onlinetext':
                $content = $submission->onlinetext;
                break;

            case 'workshop_submissions':
                $content = $submission->content;
                break;
        }*/

        /*if (self::is_content_changed(isset($content) ? $content : '', $event->other['content'])) {
        }*/

        $plagiarismentitys = [];
        $file = $unplagcore->create_file_from_content($event);

        if (parent::is_submition_draft($event)){
            return;
        }

        if ($file) {
            $plagiarismentity = $unplagcore->get_plagiarism_entity($file);
            $plagiarismentity->upload_file_on_unplag_server();
            array_push($plagiarismentitys, $plagiarismentity);
        }

        self::after_hanle_event($plagiarismentitys);
    }

    /**
     * @param $objecttable
     *
     * @return string
     */
    /*private static function handle_object_table($objecttable) {
        switch ($objecttable) {
            case 'assign_submission':
                $table = 'assignsubmission_onlinetext';
                break;

            default:
                $table = $objecttable;
                break;
        }

        return $table;
    }*/

    /**
     * @param $oldcontent
     * @param $newcontent
     *
     * @return bool
     */
    /*private static function is_content_changed($oldcontent, $newcontent) {
        return base64_encode($oldcontent) !== base64_encode($newcontent);
    }*/
}