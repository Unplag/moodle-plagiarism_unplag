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

use context_module;
use core\event\base;
use plagiarism_unplag;
use plagiarism_unplag\classes\entities\unplag_archive;
use plagiarism_unplag\classes\helpers\unplag_check_helper;
use plagiarism_unplag\classes\plagiarism\unplag_file;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

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
    /**
     * @var unplag_plagiarism_entity
     */
    private $unplagplagiarismentity;
    /** @var  bool */
    private $teamsubmission = false;
    /**
     * @var int
     */
    public $userid = null;
    /**
     * @var int
     */
    public $cmid = null;

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

            if (plagiarism_unplag::is_archive($file)) {
                $unplagarchive = new unplag_archive($file, $ucore);
                $unplagarchive->restart_check();

                return;
            }

            $plagiarismentity = $ucore->get_plagiarism_entity($file);
            $internalfile = $plagiarismentity->get_internal_file();

            unplag_check_helper::run_plagiarism_detection($plagiarismentity, $internalfile);
        }
    }

    /**
     * @param      $file
     *
     * @return null|unplag_file|unplag_plagiarism_entity
     */
    public function get_plagiarism_entity($file) {
        if (empty($file)) {
            return null;
        }

        $this->unplagplagiarismentity = new unplag_file($this, $file);

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

        $filerecord = $DB->get_records('files', array(
            'contextid'   => $contextid,
            'component'   => UNPLAG_PLAGIN_NAME,
            'contenthash' => $contenthash,
        ), 'id desc', '*', 0, 1);

        if (!$filerecord) {
            return null;
        }

        return get_file_storage()->get_file_instance(array_shift($filerecord));
    }

    public static function migrate_users_access() {
        global $DB;

        $users = $DB->get_records_sql(sprintf('SELECT user_id
            FROM {%s}
            JOIN {%s} ON (user_id = userid)
            GROUP BY user_id', UNPLAG_USER_DATA_TABLE, UNPLAG_FILES_TABLE));

        foreach ($users as $user) {
            $user = $DB->get_record('user', array('id' => $user->user_id));
            if ($user) {
                unplag_api::instance()->user_create($user);
            }
        }
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
     * @param string $url
     * @param int    $cmid
     */
    public static function inject_comment_token(&$url, $cmid) {
        $url .= '&ctoken=' . self::get_external_token($cmid);
    }

    /**
     * @param             $cmid
     * @param null|object $user
     *
     * @return mixed
     */
    public static function get_external_token($cmid, $user = null) {
        global $DB;

        $user = $user ? $user : self::get_user();

        $storeduser = $DB->get_record(UNPLAG_USER_DATA_TABLE, array('user_id' => $user->id));

        if ($storeduser) {
            return $storeduser->external_token;
        } else {
            $resp = unplag_api::instance()->user_create($user, self::is_teacher($cmid));

            if ($resp && $resp->result) {
                $externaluserdata = new \stdClass;
                $externaluserdata->user_id = $user->id;
                $externaluserdata->external_user_id = $resp->user->id;
                $externaluserdata->external_token = $resp->user->token;

                $DB->insert_record(UNPLAG_USER_DATA_TABLE, $externaluserdata);

                return $externaluserdata->external_token;
            }
        }
    }

    /**
     * @param $cmid
     *
     * @return bool
     */
    public static function is_teacher($cmid) {
        return self::can('moodle/grade:edit', $cmid);
    }

    /**
     * @param $cmid
     * @param $permission
     *
     * @return bool
     */
    public static function can($permission, $cmid) {
        global $USER;

        return has_capability($permission, context_module::instance($cmid), $USER->id);
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

    /**
     * @return $this
     */
    public function enable_teamsubmission() {
        $this->teamsubmission = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function is_teamsubmission_mode() {
        return $this->teamsubmission;
    }

    /**
     * @param null|int $uid
     *
     * @return object
     */
    public static function get_user($uid = null) {
        global $USER, $DB;

        if ($uid !== null) {
            return $DB->get_record('user', array('id' => $uid));
        }

        return $USER;
    }
}