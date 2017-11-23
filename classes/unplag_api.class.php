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
 * unplag_api.class.php - SDK for working with unplag api.
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_api
 *
 * @package plagiarism_unplag\classes
 */
class unplag_api {
    const ACCESS_SCOPE_WRITE = 'w';
    const ACCESS_SCOPE_READ = 'r';
    const CHECK_PROGRESS = 'check/progress';
    const CHECK_GET = 'check/get';
    const FILE_UPLOAD = 'file/upload';
    const CHECK_CREATE = 'check/create';
    const CHECK_DELETE = 'check/delete';
    const USER_CREATE = 'user/create';
    /**
     * @var null|unplag_api
     */
    private static $instance = null;

    /**
     * @return null|static
     */
    final public static function instance() {
        return isset(self::$instance) ? self::$instance : self::$instance = new unplag_api();
    }

    /**
     * @param string|resource $content
     * @param string          $filename
     * @param string          $format
     * @param integer         $cmid
     * @param object|null     $owner
     *
     * @return \stdClass
     */
    public function upload_file(&$content, $filename, $format = 'html', $cmid, $owner = null) {

        set_time_limit(UNPLAG_UPLOAD_TIME_LIMIT);

        if (is_resource($content)) {
            $content = stream_get_contents($content);
        }

        $postdata = [
            'format'    => strtolower($format),
            'file_data' => base64_encode($content),
            'name'      => $filename,
            'options'   => [
                'utoken'        => unplag_core::get_external_token($cmid, $owner),
                'submission_id' => $cmid,
            ],
        ];

        $content = null;

        if ($noindex = unplag_settings::get_assign_settings($cmid, unplag_settings::NO_INDEX_FILES)) {
            $postdata['options']['no_index'] = $noindex;
        }

        $response = unplag_api_request::instance()->http_post()->request(self::FILE_UPLOAD, $postdata);
        if (!is_object($response)) {
            $response = (object) [
                "result" => false,
                "errors" => [
                    [
                        "message"      => \plagiarism_unplag::trans('unknownwarning'),
                        "error_code"   => "invalid_response",
                        "extra_params" => null
                    ]
                ]
            ];
        }

        return $response;
    }

    /**
     * @param \stdClass $file
     *
     * @return \stdClass
     */
    public function run_check(\stdClass $file) {
        global $CFG;

        if (empty($file)) {
            throw new \InvalidArgumentException('Invalid argument $file');
        }

        $checktype = unplag_settings::get_assign_settings($file->cm, 'check_type');

        $options = [];
        $this->advanced_check_options($file->cm, $options);

        $postdata = [
            'type'         => is_null($checktype) ? UNPLAG_CHECK_TYPE_WEB : $checktype,
            'file_id'      => $file->external_file_id,
            'callback_url' => sprintf('%1$s%2$s&token=%3$s', $CFG->wwwroot, UNPLAG_CALLBACK_URL, $file->identifier),
            'options'      => $options,
        ];

        if (unplag_settings::get_assign_settings($file->cm, 'exclude_citations')) {
            $postdata = array_merge($postdata, ['exclude_citations' => 1, 'exclude_references' => 1]);
        }

        return unplag_api_request::instance()->http_post()->request(self::CHECK_CREATE, $postdata);
    }

    /**
     * @param array $checkids
     *
     * @return mixed
     */
    public function get_check_progress(array $checkids) {
        if (empty($checkids)) {
            throw new \InvalidArgumentException('Invalid argument $checkids');
        }

        return unplag_api_request::instance()->http_get()->request(self::CHECK_PROGRESS, [
            'id' => implode(',', $checkids),
        ]);
    }

    /**
     * @param $id
     *
     * @return \stdClass
     */
    public function get_check_data($id) {
        if (empty($id)) {
            throw new \InvalidArgumentException('Invalid argument id');
        }

        return unplag_api_request::instance()->http_get()->request(self::CHECK_GET, [
            'id' => $id,
        ]);
    }

    /**
     * @param \stdClass $file
     *
     * @return mixed
     */
    public function delete_check(\stdClass $file) {
        if (empty($file->check_id)) {
            throw new \InvalidArgumentException('Invalid argument check_id');
        }

        return unplag_api_request::instance()->http_post()->request(self::CHECK_DELETE, [
            'id' => $file->check_id,
        ]);
    }

    /**
     * @param      $user
     * @param bool $cancomment
     *
     * @return mixed
     */
    public function user_create($user, $cancomment = false) {
        $postdata = [
            'sys_id'    => $user->id,
            'email'     => $user->email,
            'firstname' => $user->firstname,
            'lastname'  => $user->lastname,
            'scope'     => $cancomment ? self::ACCESS_SCOPE_WRITE : self::ACCESS_SCOPE_READ,
        ];

        return unplag_api_request::instance()->http_post()->request(self::USER_CREATE, $postdata);
    }

    /**
     * @param $cmid
     * @param $options
     */
    private function advanced_check_options($cmid, &$options) {
        $options['exclude_self_plagiarism'] = 1;

        $similaritysensitivity = unplag_settings::get_assign_settings($cmid, unplag_settings::SENSITIVITY_SETTING_NAME);
        if (!empty($similaritysensitivity)) {
            $options['sensitivity'] = $similaritysensitivity / 100;
        }
    }
}