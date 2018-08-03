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
 * unplag_api_request.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes;

use plagiarism_unplag\library\OAuth\OAuthConsumer;
use plagiarism_unplag\library\OAuth\OAuthRequest;
use plagiarism_unplag\library\OAuth\Signature\OAuthSignatureMethod_HMAC_SHA1;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_api_request
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_api_request {
    /**
     * @var null|unplag_api_request
     */
    private static $instance = null;

    /**
     * @var  string|array
     */
    private $requestdata;

    /**
     * @var string
     */
    private $tokensecret = '';

    /**
     * @var  string
     */
    private $url;

    /**
     * @var  string
     */
    private $httpmethod = 'get';

    /**
     * Get instance
     *
     * @return null|static
     */
    final public static function instance() {
        return isset(self::$instance) ? self::$instance : self::$instance = new unplag_api_request();
    }

    /**
     * Set request method post
     *
     * @return $this
     */
    public function http_post() {
        $this->httpmethod = 'post';

        return $this;
    }

    /**
     * Set request method get
     *
     * @return $this
     */
    public function http_get() {
        $this->httpmethod = 'get';

        return $this;
    }

    /**
     * Make request
     *
     * @param string $method
     * @param array  $data
     *
     * @return \stdClass
     * @throws \coding_exception
     */
    public function request($method, $data) {
        $this->set_request_data($data);
        $this->set_action($method);

        $domain = [];
        preg_match('/\/([a-zA-Z0-9-\.]+)\//', new \moodle_url('/'), $domain);

        $ch = new \curl();
        $ch->setHeader($this->gen_oauth_headers());
        $ch->setHeader('Content-Type: application/json');
        $ch->setHeader('Plugin-Identifier: ' . $domain[1]);
        $ch->setopt([
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_CONNECTTIMEOUT' => 10,
        ]);

        if (UNPLAG_DEBUG_MODE) {
            $ch->setopt([
                'CURLOPT_SSL_VERIFYHOST' => false,
                'CURLOPT_SSL_VERIFYPEER' => false,
            ]);
        }

        $resp = $ch->{$this->httpmethod}($this->url, $this->get_request_data());

        return $this->handle_response($resp);
    }

    /**
     * Set request data
     *
     * @param array $requestdata
     */
    private function set_request_data(&$requestdata) {
        if ($this->httpmethod === 'get') {
            $this->requestdata = $requestdata;
        } else {
            $this->requestdata = json_encode($requestdata);
        }
    }

    /**
     * Set action
     *
     * @param string $url
     */
    private function set_action($url) {
        $this->url = UNPLAG_API_URL . $url;
    }

    /**
     * Generate oauth headers
     *
     * @return string
     */
    private function gen_oauth_headers() {
        $oauthdata = [];
        if ($this->httpmethod == 'post') {
            $oauthdata['oauth_body_hash'] = $this->gen_oauth_body_hash();
        } else {
            $oauthdata = $this->get_request_data();
        }

        $oauthconsumer = new OAuthConsumer(unplag_settings::get_settings('client_id'), unplag_settings::get_settings('api_secret'));
        $oauthreq = OAuthRequest::from_consumer_and_token(
            $oauthconsumer, $this->get_token_secret(), $this->httpmethod, $this->get_url(), $oauthdata
        );
        $oauthreq->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $oauthconsumer, $this->get_token_secret());

        return $oauthreq->to_header();
    }

    /**
     * gen_oauth_body_hash
     *
     * @return string
     */
    private function gen_oauth_body_hash() {
        return base64_encode(sha1($this->get_request_data(), true));
    }

    /**
     * get_request_data
     *
     * @return string
     */
    public function get_request_data() {
        return $this->requestdata;
    }

    /**
     * get_token_secret
     *
     * @return string
     */
    public function get_token_secret() {
        return $this->tokensecret;
    }

    /**
     * Get request url
     *
     * @return string
     */
    public function get_url() {
        return $this->url;
    }

    /**
     * Handle response
     *
     * @param string $resp
     *
     * @return \stdClass
     */
    private function handle_response($resp) {
        return json_decode($resp);
    }
}