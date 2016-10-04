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

namespace plagiarism_unplag\classes;

use core\event\base;
use plagiarism_unplag;
use plagiarism_unplag\classes\exception\UnplagException;

/**
 * Class unplag_core
 *
 * @package     plagiarism_unplag\classes
 * @subpackage  plagiarism
 * @namespace   plagiarism_unplag\classes
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_core {
    /** @var unplag_plagiarism_entity */
    private $unplagplagiarismentity;

    /**
     * unplag_core constructor.
     *
     * @param $cmid
     * @param $userid
     */
    public function __construct($cmid, $userid) {
        $this->cmid = $cmid;
        $this->userid = $userid;
    }

    /**
     * @param $cid
     * @param $checkstatusforids
     * @param $resp
     *
     * @throws UnplagException
     */
    public static function check_real_file_progress($cid, $checkstatusforids, &$resp) {
        $progresses = unplag_api::instance()->get_check_progress($checkstatusforids);
        if ($progresses->result) {
            foreach ($progresses->progress as $id => $val) {
                $val *= 100;
                $fileobj = self::update_file_progress($id, $val);
                $resp[$id]['progress'] = $val;
                $resp[$id]['content'] = plagiarism_unplag::gen_row_content_score($cid, $fileobj);
            }
        }
    }

    /**
     * @param $id
     * @param $progres
     *
     * @return mixed
     * @throws UnplagException
     */
    private static function update_file_progress($id, $progres) {
        global $DB;

        $record = $DB->get_record(UNPLAG_FILES_TABLE, array('check_id' => $id));
        if ($record->progress <= $progres) {
            $record->progress = $progres;

            if ($record->progress === 100) {
                $resp = unplag_api::instance()->get_check_data($id);
                if (!$resp->result) {
                    throw new UnplagException($resp->errors);
                }

                self::check_complete($record, $resp->check);
            } else {
                $DB->update_record(UNPLAG_FILES_TABLE, $record);
            }
        }

        return $record;
    }

    /**
     * @param \stdClass $record
     * @param \stdClass $check
     */
    public static function check_complete(\stdClass &$record, \stdClass $check) {
        global $DB;

        $record->statuscode = UNPLAG_STATUSCODE_PROCESSED;
        $record->similarityscore = $check->report->similarity;
        $record->reporturl = $check->report->view_url;
        $record->reportediturl = $check->report->view_edit_url;
        $record->progress = 100;

        $updated = $DB->update_record(UNPLAG_FILES_TABLE, $record);

        $emailstudents = unplag_settings::get_assign_settings($record->cm, 'unplag_studentemail');
        if ($updated && !empty($emailstudents)) {
            unplag_notification::send_student_email_notification($record);
        }
    }

    /**
     * @param $data
     *
     * @return string
     */
    public static function json_response($data) {
        return json_encode($data);
    }

    /**
     * @param $id
     *
     * @return null
     * @throws \coding_exception
     */
    public static function resubmit_file($id) {
        global $DB;

        $plagiarismfile = $DB->get_record(UNPLAG_FILES_TABLE, array('id' => $id), '*', MUST_EXIST);
        if (in_array($plagiarismfile->statuscode, array(UNPLAG_STATUSCODE_PROCESSED, UNPLAG_STATUSCODE_ACCEPTED))) {
            // Sanity Check.
            return null;
        }

        $cm = get_coursemodule_from_id('', $plagiarismfile->cm);

        if (plagiarism_unplag::is_support_mod($cm->modname)) {
            $file = get_file_storage()->get_file_by_hash($plagiarismfile->identifier);
            $ucore = new unplag_core($plagiarismfile->cm, $plagiarismfile->userid);
            $plagiarismentity = $ucore->get_plagiarism_entity($file);
            $internalfile = $plagiarismentity->get_internal_file();
            if (isset($internalfile->external_file_id)) {
                if ($internalfile->check_id) {
                    unplag_api::instance()->delete_check($internalfile);
                }

                unplag_notification::success('plagiarism_run_success', true);

                $checkresp = unplag_api::instance()->run_check($internalfile);
                $plagiarismentity->handle_check_response($checkresp);
            } else {
                $error = self::parse_json($internalfile->errorresponse);
                unplag_notification::error('Can\'t restart check: ' . $error[0]->message, false);
            }
        }
    }

    /**
     * @param $file
     *
     * @return null|unplag_plagiarism_entity
     */
    public function get_plagiarism_entity($file) {
        if (empty($file)) {
            return null;
        }

        $this->unplagplagiarismentity = new unplag_plagiarism_entity($this, $file);

        return $this->unplagplagiarismentity;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function parse_json($data) {
        return json_decode($data);
    }

    /**
     * @param $contextid
     * @param $contenthash
     *
     * @return null|\stored_file
     */
    public static function get_file_by_hash($contextid, $contenthash) {
        global $DB;

        $filerecord = $DB->get_record('files', array(
            'contextid'   => $contextid,
            'component'   => UNPLAG_PLAGIN_NAME,
            'contenthash' => $contenthash,
        ));

        if (!$filerecord) {
            return null;
        }

        return get_file_storage()->get_file_instance($filerecord);
    }

    /**
     * @param base $event
     *
     * @return bool|\stored_file
     *
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function create_file_from_content(base $event) {
        global $USER;

        if (empty($event->other['content'])) {
            return false;
        }

        $filerecord = array(
            'component' => UNPLAG_PLAGIN_NAME,
            'filearea'  => $event->objecttable,
            'contextid' => $event->contextid,
            'itemid'    => $event->objectid,
            'filename'  => sprintf("%s-content-%d-%d-%d.html",
                str_replace('_', '-', $event->objecttable), $event->contextid, $this->cmid, $event->objectid
            ),
            'filepath'  => '/',
            'userid'    => $USER->id,
            'license'   => 'allrightsreserved',
            'author'    => $USER->firstname . ' ' . $USER->lastname,
        );

        /** @var \stored_file $storedfile */
        $storedfile = get_file_storage()->get_file(
            $filerecord['contextid'], $filerecord['component'], $filerecord['filearea'],
            $filerecord['itemid'], $filerecord['filepath'], $filerecord['filename']
        );

        if ($storedfile && $storedfile->get_contenthash() != self::content_hash($event->other['content'])) {
            $this->delete_old_file_from_content($storedfile);
        }

        return get_file_storage()->create_file_from_string($filerecord, $event->other['content']);
    }

    /**
     * @param $content
     *
     * @return string
     */
    public static function content_hash($content) {
        return sha1($content);
    }

    /**
     * @param $url
     */
    public static function inject_comment_token(&$url) {
        global $USER;

        $resp = unplag_api::instance()->user_create($USER);
        $url .= '&ctoken=' . $resp->user->token;
    }

    /**
     * @param \stored_file $storedfile
     */
    private function delete_old_file_from_content(\stored_file $storedfile) {
        global $DB;

        $DB->delete_records(UNPLAG_FILES_TABLE, array(
            'cm'         => $this->cmid,
            'userid'     => $storedfile->get_userid(),
            'identifier' => $storedfile->get_pathnamehash(),
        ));

        $storedfile->delete();
    }
}