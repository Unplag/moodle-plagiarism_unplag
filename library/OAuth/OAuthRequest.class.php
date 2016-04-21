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

namespace plagiarism_unplag\library\OAuth;

/**
 * Class OAuthRequest
 * @package plagiarism_unplag\library\OAuth
 */
class OAuthRequest {
    /** @var string */
    public static $version = '1.0';
    /** @var string */
    public static $POST_INPUT = 'php://input';
    /** @var string */
    public $base_string;
    /** @var array|null */
    private $parameters;
    /** @var string */
    private $http_method;
    /** @var string */
    private $http_url;

    /**
     * OAuthRequest constructor.
     *
     * @param      $http_method
     * @param      $http_url
     * @param null $parameters
     */
    public function __construct($http_method, $http_url, $parameters = null) {
        @$parameters or $parameters = [];
        $this->parameters = $parameters;
        $this->http_method = $http_method;
        $this->http_url = $http_url;
    }

    /**
     * attempt to build up a request from what was passed to the server
     */
    public static function from_request($http_method = null, $http_url = null, $parameters = null) {
        $scheme = (!is_https()) ? 'http' : 'https';
        $port = "";
        if ($_SERVER['SERVER_PORT'] != "80" && $_SERVER['SERVER_PORT'] != "443" && strpos(':', $_SERVER['HTTP_HOST']) < 0) {
            $port = ':' . $_SERVER['SERVER_PORT'];
        }
        @$http_url or $http_url = $scheme .
            '://' . $_SERVER['HTTP_HOST'] .
            $port .
            $_SERVER['REQUEST_URI'];
        @$http_method or $http_method = $_SERVER['REQUEST_METHOD'];

        // We weren't handed any parameters, so let's find the ones relevant to
        // this request.
        // If you run XML-RPC or similar you should use this to provide your own
        // parsed parameter-list.
        if (!$parameters) {
            // Find request headers.
            $request_headers = OAuthUtil::get_headers();

            // Parse the query-string to find GET parameters.
            $parameters = OAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);

            $ourpost = $_POST;
            // Deal with magic_quotes
            // http://www.php.net/manual/en/security.magicquotes.disabling.php.
            if (get_magic_quotes_gpc()) {
                $outpost = [];
                foreach ($_POST as $k => $v) {
                    $v = stripslashes($v);
                    $ourpost[$k] = $v;
                }
            }
            // Add POST Parameters if they exist.
            $parameters = array_merge($parameters, $ourpost);

            // We have a Authorization-header with OAuth data. Parse the header
            // and add those overriding any duplicates from GET or POST.
            if (@substr($request_headers['Authorization'], 0, 6) == "OAuth ") {
                $header_parameters = OAuthUtil::split_header($request_headers['Authorization']);
                $parameters = array_merge($parameters, $header_parameters);
            }
        }

        return new OAuthRequest($http_method, $http_url, $parameters);
    }

    /**
     * pretty much a helper function to set up the request
     */
    public static function from_consumer_and_token($consumer, $token, $http_method, $http_url, $parameters = null) {
        @$parameters or $parameters = [];
        $defaults = [
            "oauth_version"      => OAuthRequest::$version,
            "oauth_nonce"        => OAuthRequest::generate_nonce(),
            "oauth_timestamp"    => OAuthRequest::generate_timestamp(),
            "oauth_consumer_key" => $consumer->key,
        ];
        if ($token) {
            $defaults['oauth_token'] = $token->key;
        }
        $parameters = array_merge($defaults, $parameters);
        // Parse the query-string to find and add GET parameters.
        $parts = parse_url($http_url);
        if (isset($parts['query'])) {
            $qparms = OAuthUtil::parse_parameters($parts['query']);
            $parameters = array_merge($qparms, $parameters);
        }

        return new OAuthRequest($http_method, $http_url, $parameters);
    }

    /**
     * util function: current nonce
     */
    private static function generate_nonce() {
        $mt = microtime();
        $rand = mt_rand();

        return md5($mt . $rand);
    }

    /**
     * util function: current timestamp
     */
    private static function generate_timestamp() {
        return time();
    }

    /**
     * @param $name
     *
     * @return null
     */
    public function get_parameter($name) {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * @return array|null
     */
    public function get_parameters() {
        return $this->parameters;
    }

    /**
     * @param $name
     */
    public function unset_parameter($name) {
        unset($this->parameters[$name]);
    }

    /**
     * Returns the base string of this request
     *
     * The base string defined as the method, the url
     * and the parameters (normalized), each urlencoded
     * and the concated with &.
     */
    public function get_signature_base_string() {
        $parts = [
            $this->get_normalized_http_method(),
            $this->get_normalized_http_url(),
            $this->get_signable_parameters(),
        ];
        $parts = OAuthUtil::urlencode_rfc3986($parts);

        return implode('&', $parts);
    }

    /**
     * just uppercases the http method
     */
    public function get_normalized_http_method() {
        return strtoupper($this->http_method);
    }

    /**
     * parses the url and rebuilds it to be
     * scheme://host/path
     */
    public function get_normalized_http_url() {
        $parts = parse_url($this->http_url);
        $port = @$parts['port'];
        $scheme = isset($parts['scheme']) ? $parts['scheme'] : false;
        $host = isset($parts['host']) ? $parts['host'] : false;
        $path = @$parts['path'];
        $port or $port = ($scheme == 'https') ? '443' : '80';
        if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')
        ) {
            $host = "$host:$port";
        }

        return "$scheme://$host$path";
    }

    /**
     * The request parameters, sorted and concatenated into a normalized string.
     * @return string
     */
    public function get_signable_parameters() {
        // Grab all parameters.
        $params = $this->parameters;
        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return OAuthUtil::build_http_query($params);
    }

    /**
     * @return string
     * @throws OAuthException
     */
    public function to_header() {
        return $this->to_header_internal('Authorization: OAuth realm=""');
    }

    /**
     * builds the Authorization: header
     */
    public function to_header_internal($start) {
        $out = $start;
        $comma = ',';
        $total = [];
        foreach ($this->parameters as $k => $v) {
            if (substr($k, 0, 5) != "oauth") {
                continue;
            }
            if (is_array($v)) {
                throw new OAuthException('Arrays not supported in headers');
            }
            $out .= $comma .
                OAuthUtil::urlencode_rfc3986($k) .
                '="' .
                OAuthUtil::urlencode_rfc3986($v) .
                '"';
            $comma = ',';
        }

        return $out;
    }

    /**
     * @return string
     * @throws OAuthException
     */
    public function to_alternate_header() {
        return $this->to_header_internal('X-Oauth1-Authorization: OAuth realm=""');
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->to_url();
    }

    /**
     * builds a url usable for a GET request
     */
    public function to_url() {
        $post_data = $this->to_postdata();
        $out = $this->get_normalized_http_url();
        if ($post_data) {
            $out .= '?' . $post_data;
        }

        return $out;
    }

    /**
     * builds the data one would send in a POST request
     */
    public function to_postdata() {
        return OAuthUtil::build_http_query($this->parameters);
    }

    /**
     * @param $signature_method
     * @param $consumer
     * @param $token
     */
    public function sign_request($signature_method, $consumer, $token) {
        $this->set_parameter(
            "oauth_signature_method",
            $signature_method->get_name(),
            false
        );
        $signature = $this->build_signature($signature_method, $consumer, $token);
        $this->set_parameter("oauth_signature", $signature, false);
    }

    /**
     * @param      $name
     * @param      $value
     * @param bool $allow_duplicates
     */
    public function set_parameter($name, $value, $allow_duplicates = true) {
        if ($allow_duplicates && isset($this->parameters[$name])) {
            // We have already added parameter(s) with this name, so add to the list.
            if (is_scalar($this->parameters[$name])) {
                // This is the first duplicate, so transform scalar (string)
                // into an array so we can add the duplicates.
                $this->parameters[$name] = [$this->parameters[$name]];
            }
            $this->parameters[$name][] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    /**
     * @param $signature_method
     * @param $consumer
     * @param $token
     *
     * @return mixed
     */
    public function build_signature($signature_method, $consumer, $token) {
        $signature = $signature_method->build_signature($this, $consumer, $token);

        return $signature;
    }

    /**
     * @param $s
     *
     * @return mixed
     */
    function urlencode($s) {
        if ($s === false) {
            return $s;
        } else {
            return str_replace('%7E', '~', rawurlencode($s));
        }
    }
}
