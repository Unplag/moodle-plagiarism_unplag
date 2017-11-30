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
 * unplag_progress.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes\helpers;

use plagiarism_unplag\classes\entities\providers\unplag_file_provider;
use plagiarism_unplag\classes\exception\unplag_exception;
use plagiarism_unplag\classes\services\storage\unplag_file_state;
use plagiarism_unplag\classes\unplag_adhoc;
use plagiarism_unplag\classes\unplag_api;
use plagiarism_unplag\classes\unplag_plagiarism_entity;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_progress
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_progress {
    /**
     * get_file_progress_info
     *
     * @param object $plagiarismfile
     * @param int    $cid
     * @param array  $checkstatusforids
     *
     * @return array|bool
     */
    public static function get_check_progress_info($plagiarismfile, $cid, &$checkstatusforids) {
        $childs = [];
        if ($plagiarismfile->type == unplag_plagiarism_entity::TYPE_ARCHIVE) {
            $childs = unplag_file_provider::get_file_list_by_parent_id($plagiarismfile->id);
        }

        if (empty($plagiarismfile->check_id) && empty($childs)) {
            return false;
        }

        if ($plagiarismfile->progress != 100) {
            if (count($childs)) {
                foreach ($childs as $child) {
                    if ($child->check_id) {
                        $checkstatusforids[$plagiarismfile->id][] = $child->check_id;
                    }
                }
            } else {
                if ($plagiarismfile->check_id) {
                    $checkstatusforids[$plagiarismfile->id][] = $plagiarismfile->check_id;
                }
            }
        }

        $info = [
            'file_id'  => $plagiarismfile->id,
            'state'    => $plagiarismfile->state,
            'progress' => (int) $plagiarismfile->progress,
            'content'  => self::gen_row_content_score($cid, $plagiarismfile),
        ];
        return $info;
    }

    /**
     * Track file upload
     *
     * @param \stdClass $plagiarismfile
     */
    public static function track_upload(\stdClass $plagiarismfile) {
        global $DB;

        $trackedfiles = [$plagiarismfile];
        if ($plagiarismfile->type == unplag_plagiarism_entity::TYPE_ARCHIVE) {
            $trackedfiles = unplag_file_provider::get_file_list_by_parent_id($plagiarismfile->id);
        }

        foreach ($trackedfiles as $trackedfile) {
            if (!$trackedfile->external_file_uuid) {
                continue;
            }

            $response = unplag_api::instance()->get_file_upload_progress($trackedfile->external_file_uuid);
            if (!$response->result) {
                unplag_response::store_errors($response->errors, $plagiarismfile);
                continue;
            }

            $progress = $response->progress;
            if ($progress->file && $progress->file->id && !$trackedfile->check_id) {
                unplag_upload_helper::upload_complete($trackedfile, $progress->file);
                unplag_adhoc::check($trackedfile);
            }
        }
    }

    /**
     * get_real_check_progress
     *
     * @param int   $cid
     * @param array $checkstatusforids
     * @param array $resp
     *
     * @throws unplag_exception
     */
    public static function get_real_check_progress($cid, $checkstatusforids, &$resp) {
        global $DB;

        $progressids = [];

        foreach ($checkstatusforids as $recordid => $checkids) {
            $progressids = array_merge($progressids, $checkids);
        }

        $progressids = array_unique($progressids);
        $progresses = unplag_api::instance()->get_check_progress($progressids);

        if ($progresses->result) {
            foreach ($progresses->progress as $id => $val) {
                $val *= 100;
                $fileobj = self::update_file_progress($id, $val);
                $resp[$fileobj->id]['progress'] = $val;
                $resp[$fileobj->id]['content'] = self::gen_row_content_score($cid, $fileobj);
            }

            foreach ($checkstatusforids as $recordid => $checkids) {
                if (count($checkids) > 0) {
                    $childscount = $DB->count_records_select(UNPLAG_FILES_TABLE, "parent_id = ? AND state not in (?)",
                        [$recordid, unplag_file_state::HAS_ERROR]) ?: 1;

                    $progress = 0;

                    foreach ($checkids as $id) {
                        $progress += ($progresses->progress->{$id} * 100);
                    }

                    $progress = floor($progress / $childscount);
                    $fileobj = self::update_parent_progress($recordid, $progress);
                    $resp[$recordid]['progress'] = $progress;
                    $resp[$recordid]['content'] = self::gen_row_content_score($cid, $fileobj);
                }
            }
        }
    }

    /**
     * gen_row_content_score
     *
     * @param int    $cid
     * @param object $fileobj
     *
     * @return bool|mixed
     */
    public static function gen_row_content_score($cid, $fileobj) {
        if ($fileobj->progress == 100 && $cid) {
            return require(dirname(__FILE__) . '/../../views/view_tmpl_processed.php');
        } else {
            if ($fileobj->state == unplag_file_state::HAS_ERROR) {
                return require(dirname(__FILE__) . '/../../views/view_tmpl_invalid_response.php');
            }
        }

        return false;
    }

    /**
     * update_file_progress
     *
     * @param int $id
     * @param int $progress
     *
     * @return mixed
     * @throws unplag_exception
     */
    private static function update_file_progress($id, $progress) {
        $record = unplag_file_provider::find_by_check_id($id);
        if ($record->progress <= $progress) {
            $record->progress = $progress;

            if ($record->progress === 100) {
                $resp = unplag_api::instance()->get_check_data($id);
                if (!$resp->result) {
                    $errors = array_shift($resp->errors);
                    throw new unplag_exception($errors->message);
                }

                unplag_check_helper::check_complete($record, $resp->check);
            } else {
                unplag_file_provider::save($record);
            }
        }

        return $record;
    }

    /**
     * update_parent_progress
     *
     * @param int $fileid
     * @param int $progress
     *
     * @return mixed
     */
    private static function update_parent_progress($fileid, $progress) {
        $record = unplag_file_provider::find_by_id($fileid);
        if ($record->progress <= $progress) {
            $record->progress = $progress;
            if ($record->progress != 100) {
                unplag_file_provider::save($record);
            }
        }

        return $record;
    }
}