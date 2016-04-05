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
 * @package    plagiarism_unplag
 * @author
 * @copyright  UKU Group, LTD, https://www.unplag.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace plagiarism_unplag\classes;

use plagiarism_unplag\library\OAuth\OAuthConsumer;
use plagiarism_unplag\library\OAuth\OAuthRequest;
use plagiarism_unplag\library\OAuth\Signature\OAuthSignatureMethod_HMAC_SHA1;

require_once(UNPLAG_PROJECT_PATH . 'library/OAuth/autoloader.php');

/**
 * Class unplag_api
 * @package plagiarism_unplag\classes
 */
class unplag_api {
    private static $instance = null;
    /** @var string */
    private static $apiurl = 'http://un16.mytheverona.com/api/v2/';
    /** @var string */
    private static $httpmethod = 'POST';
    private static $checktype = 'web';
    /** @var  string */
    private $requestdata;
    /** @var string */
    private $tokensecret = '';
    /** @var  string */
    private $url;

    /**
     * @return null|static
     */
    final public static function instance() {
        return isset(static::$instance) ? static::$instance : static::$instance = new static;
    }

    /**
     * @param \stored_file $file
     *
     * @return mixed
     * @throws \file_exception
     */
    public function upload_file(\stored_file $file) {
        $postdata = [
            'format'    => pathinfo($file->get_source(), PATHINFO_EXTENSION),
            'file_data' => base64_encode($file->get_content()),
            'name'      => $file->get_filename(),
        ];

        return $this->_request('file/upload', $postdata);
    }

    /**
     * @param $method
     * @param $real_post
     *
     * @return bool
     * @throws \coding_exception
     */
    private function _request($method, $real_post) {
        $this->set_request_data($real_post);
        $this->set_action($method);

        $ch = new \curl();
        $ch->setHeader($this->gen_oauth_headers());
        $ch->setHeader('Content-Type: application/json');
        $ch->setopt(['CURLOPT_RETURNTRANSFER' => true]);

        $resp = $ch->post($this->url, $this->get_request_data());

        return $this->handle_response($resp);
    }

    /* @param mixed $url */
    public function set_action($url) {
        $this->url = self::$apiurl . $url;
    }

    /**
     * @return string
     */
    private function gen_oauth_headers() {
        $oauth_data['oauth_body_hash'] = $this->gen_oauth_body_hash();
        $settings = unplag_core::get_settings();
        $oauth_consumer = new OAuthConsumer($settings['unplag_client_id'], $settings['unplag_api_secret']);
        $oauthreq = OAuthRequest::from_consumer_and_token(
            $oauth_consumer, $this->get_token_secret(), self::$httpmethod, $this->get_url(), $oauth_data
        );
        $oauthreq->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $oauth_consumer, $this->get_token_secret());

        return $oauthreq->to_header();
    }

    /**
     * @return string
     */
    private function gen_oauth_body_hash() {
        return base64_encode(sha1($this->get_request_data(), true));
    }

    /**
     * @return mixed
     */
    public function get_request_data() {
        return $this->requestdata;
    }

    /**
     * @param $requestdata
     */
    public function set_request_data($requestdata) {
        $this->requestdata = json_encode($requestdata);
    }

    /**
     * @return string
     */
    public function get_token_secret() {
        return $this->tokensecret;
    }

    /**
     * @return mixed
     */
    public function get_url() {
        return $this->url;
    }

    /**
     * @param $resp
     *
     * @return \stdClass
     */
    private function handle_response($resp) {
        return json_decode($resp);
    }

    /**
     * @param \stdClass $file
     *
     * @return bool
     */
    public function run_check(\stdClass $file) {
        $postdata = [
            'type'    => self::$checktype,
            'file_id' => $file->id,
        ];

        return $this->_request('check/create', $postdata);
    }

    final private function __wakeup() {
    }

    final private function __clone() {
    }
}