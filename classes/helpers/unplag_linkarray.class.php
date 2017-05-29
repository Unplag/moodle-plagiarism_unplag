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

namespace plagiarism_unplag\classes\helpers;

use plagiarism_unplag\classes\unplag_assign;
use plagiarism_unplag\classes\unplag_plagiarism_entity;
use plagiarism_unplag\classes\unplag_workshop;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_linkarray
 *
 * @package     plagiarism_unplag\classes
 * @subpackage  plagiarism
 * @namespace   plagiarism_unplag\classes
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_linkarray {
    /**
     * @param $cm
     * @param $linkarray
     *
     * @return mixed|null|\stored_file
     */
    public static function get_file_from_linkarray($cm, $linkarray) {
        $file = null;
        if (isset($linkarray['content'])) {
            $context = \context_module::instance($linkarray['cmid']);
            switch ($cm->modname) {
                case UNPLAG_MODNAME_WORKSHOP:
                    $workshopsubmission = unplag_workshop::get_user_workshop_submission_by_cm($cm, $linkarray['userid']);
                    $files = \plagiarism_unplag::get_area_files($context->id, UNPLAG_WORKSHOP_FILES_AREA, $workshopsubmission->id);
                    $file = array_shift($files);
                    break;
                case UNPLAG_MODNAME_FORUM:
                    $file = \plagiarism_unplag::get_forum_topic_results($context, $linkarray);
                    break;
                case UNPLAG_MODNAME_ASSIGN:
                    $submission = unplag_assign::get_user_submission_by_cmid($linkarray['cmid'], $linkarray['userid']);
                    $itemid = $submission ? $submission->id : null;
                    $files = \plagiarism_unplag::get_area_files($context->id, UNPLAG_DEFAULT_FILES_AREA, $itemid);
                    $file = array_shift($files);
                    break;
                default:
                    $files = \plagiarism_unplag::get_area_files($context->id, UNPLAG_DEFAULT_FILES_AREA);
                    $file = array_shift($files);
                    break;
            }
        } else {
            if (isset($linkarray['file'])) {
                $file = $linkarray['file'];
            }
        }

        return $file;
    }

    /**
     * @param \stdClass $fileobj
     * @param           $cm
     * @param           $linkarray
     *
     * @return mixed
     */
    public static function get_output_for_linkarray(\stdClass $fileobj, $cm, $linkarray) {
        static $iterator; // This iterator for one-time start-up.

        $tmpl = null;
        $inciterator = false;

        switch ($fileobj->statuscode) {
            case UNPLAG_STATUSCODE_PROCESSED:
                $tmpl = 'view_tmpl_processed.php';
                break;
            case UNPLAG_STATUSCODE_ACCEPTED:
                if (isset($fileobj->check_id) || $fileobj->type == unplag_plagiarism_entity::TYPE_ARCHIVE) {
                    $tmpl = 'view_tmpl_accepted.php';
                    $inciterator = true;
                } else {
                    $tmpl = 'view_tmpl_unknownwarning.php';
                }
                break;
            case UNPLAG_STATUSCODE_INVALID_RESPONSE:
                $tmpl = 'view_tmpl_invalid_response.php';
                break;
            case UNPLAG_STATUSCODE_PENDING:
                if (self::is_pending($cm, $fileobj) && self::is_submission_submitted($linkarray)) {
                    $tmpl = 'view_tmpl_can_check.php';
                    $inciterator = true;
                }
                break;
            default:
                $tmpl = 'view_tmpl_unknownwarning.php';
                break;
        }

        $output = is_null($tmpl) ? '' : require(dirname(__FILE__) . '/../../views/' . $tmpl);

        if ($inciterator) {
            $iterator++;
        }

        return $output;
    }

    /**
     * @param $cm
     * @param $fileobj
     *
     * @return bool
     */
    private static function is_pending($cm, $fileobj) {
        return $cm->modname == UNPLAG_MODNAME_ASSIGN && empty($fileobj->check_id) ;
    }

    /**
     * @param $linkarray
     *
     * @return bool
     */
    private static function is_submission_submitted($linkarray) {
        $submission = unplag_assign::get_user_submission_by_cmid($linkarray['cmid'], $linkarray['userid']);

        return $submission->status == 'submitted';
    }
}