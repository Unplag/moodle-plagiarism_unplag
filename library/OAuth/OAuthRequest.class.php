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
 * OAuthRequest.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\library\OAuth;

use plagiarism_unplag\library\OAuth\Signature\OAuthSignatureMethod;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class OAuthRequest
 *
 * @package     plagiarism_unplag
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class OAuthRequest {
    /** @var string */
    public static $version = '1.0';
    /** @var string */
    public $basestring;
    /** @var array|null */
    private $parameters;
    /** @var string */
    private $httpmethod;
    /** @var string */
    private $httpurl;

    /**
     * OAuthRequest constructor.
     *
     * @param string $httpmethod
     * @param string $httpurl
     * @param null   $parameters
     */
    public function __construct($httpmethod, $httpurl, $parameters = null) {
        @$parameters or $parameters = array();
        $this->parameters = $parameters;
        $this->httpmethod = $httpmethod;
        $this->httpurl = $httpurl;
    }

    /**
     * attempt to build up a request from what was passed to the server
     *
     * @param null $httpmethod
     * @param null $httpurl
     * @param null $parameters
     *
     * @return OAuthRequest
     */
    public static function from_request($httpmethod = null, $httpurl = null, $parameters = null) {
        $scheme = (!is_https()) ? 'http' : 'https';
        $port = "";
        if ($_SERVER['SERVER_PORT'] != "80" && $_SERVER['SERVER_PORT'] != "443" && strpos(':', $_SERVER['HTTP_HOST']) < 0) {
            $port = ':' . $_SERVER['SERVER_PORT'];
        }
        @$httpurl or $httpurl = $scheme .
            '://' . $_SERVER['HTTP_HOST'] .
            $port .
            $_SERVER['REQUEST_URI'];
        @$httpmethod or $httpmethod = $_SERVER['REQUEST_METHOD'];

        // We weren't handed any parameters, so let's find the ones relevant to
        // this request.
        // If you run XML-RPC or similar you should use this to provide your own
        // parsed parameter-list.
        if (!$parameters) {
            // Find request headers.
            $requestheaders = OAuthUtil::get_headers();

            // Parse the query-string to find GET parameters.
            $parameters = OAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);

            $ourpost = $_POST;
            // Deal with magic_quotes
            // http://www.php.net/manual/en/security.magicquotes.disabling.php.
            if (get_magic_quotes_gpc()) {
                $outpost = array();
                foreach ($_POST as $k => $v) {
                    $v = stripslashes($v);
                    $ourpost[$k] = $v;
                }
            }
            // Add POST Parameters if they exist.
            $parameters = array_merge($parameters, $ourpost);

            // We have a Authorization-header with OAuth data. Parse the header
            // and add those overriding any duplicates from GET or POST.
            if (@substr($requestheaders['Authorization'], 0, 6) == "OAuth ") {
                $headerparameters = OAuthUtil::split_header($requestheaders['Authorization']);
                $parameters = array_merge($parameters, $headerparameters);
            }
        }

        return new OAuthRequest($httpmethod, $httpurl, $parameters);
    }

    /**
     * pretty much a helper function to set up the request
     *
     * @param object $consumer
     * @param string $token
     * @param string $httpmethod
     * @param string $httpurl
     * @param null   $parameters
     *
     * @return OAuthRequest
     */
    public static function from_consumer_and_token($consumer, $token, $httpmethod, $httpurl, $parameters = null) {
        @$parameters or $parameters = array();
        $defaults = array(
            "oauth_version"      => self::$version,
            "oauth_nonce"        => self::generate_nonce(),
            "oauth_timestamp"    => self::generate_timestamp(),
            "oauth_consumer_key" => $consumer->key,
        );
        if ($token) {
            $defaults['oauth_token'] = $token->key;
        }
        $parameters = array_merge($defaults, $parameters);
        // Parse the query-string to find and add GET parameters.
        $parts = parse_url($httpurl);
        if (isset($parts['query'])) {
            $qparms = OAuthUtil::parse_parameters($parts['query']);
            $parameters = array_merge($qparms, $parameters);
        }

        return new OAuthRequest($httpmethod, $httpurl, $parameters);
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
     * get_parameter
     *
     * @param string $name
     *
     * @return null
     */
    public function get_parameter($name) {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * get_parameters
     *
     * @return array|null
     */
    public function get_parameters() {
        return $this->parameters;
    }

    /**
     * unset_parameter
     *
     * @param string $name
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
        $parts = array(
            $this->get_normalized_http_method(),
            $this->get_normalized_http_url(),
            $this->get_signable_parameters(),
        );
        $parts = OAuthUtil::urlencode_rfc3986($parts);

        return implode('&', $parts);
    }

    /**
     * just uppercases the http method
     */
    public function get_normalized_http_method() {
        return strtoupper($this->httpmethod);
    }

    /**
     * parses the url and rebuilds it to be
     * scheme://host/path
     */
    public function get_normalized_http_url() {
        $parts = parse_url($this->httpurl);
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
     *
     * @return string
     */
    public function get_signable_parameters() {
        // Grab all parameters.
        $params = $this->parameters;
        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.") .
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return OAuthUtil::build_http_query($params);
    }

    /**
     * Set to header
     *
     * @return string
     * @throws OAuthException
     */
    public function to_header() {
        return $this->to_header_internal('Authorization: OAuth realm=""');
    }

    /**
     * builds the Authorization: header
     *
     * @param mixed $start
     */
    public function to_header_internal($start) {
        $out = $start;
        $comma = ',';

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
     * Set alternate header
     *
     * @return string
     * @throws OAuthException
     */
    public function to_alternate_header() {
        return $this->to_header_internal('X-Oauth1-Authorization: OAuth realm=""');
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString() {
        return $this->to_url();
    }

    /**
     * builds a url usable for a GET request
     */
    public function to_url() {
        $postdata = $this->to_postdata();
        $out = $this->get_normalized_http_url();
        if ($postdata) {
            $out .= '?' . $postdata;
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
     * Sign request
     *
     * @param OAuthSignatureMethod $signaturemethod
     * @param object               $consumer
     * @param mixed                $token
     */
    public function sign_request(OAuthSignatureMethod $signaturemethod, $consumer, $token) {
        $this->set_parameter(
            "oauth_signature_method",
            $signaturemethod->get_name(),
            false
        );
        $signature = $this->build_signature($signaturemethod, $consumer, $token);
        $this->set_parameter("oauth_signature", $signature, false);
    }

    /**
     * Set parameter
     *
     * @param string $name
     * @param mixed  $value
     * @param bool   $allowduplicates
     */
    public function set_parameter($name, $value, $allowduplicates = true) {
        if ($allowduplicates && isset($this->parameters[$name])) {
            // We have already added parameter(s) with this name, so add to the list.
            if (is_scalar($this->parameters[$name])) {
                // This is the first duplicate, so transform scalar (string)
                // into an array so we can add the duplicates.
                $this->parameters[$name] = array($this->parameters[$name]);
            }
            $this->parameters[$name][] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    /**
     * Build signature
     *
     * @param OAuthSignatureMethod $signaturemethod
     * @param object               $consumer
     * @param mixed                $token
     *
     * @return mixed
     */
    public function build_signature(OAuthSignatureMethod $signaturemethod, $consumer, $token) {
        $signature = $signaturemethod->build_signature($this, $consumer, $token);

        return $signature;
    }

    /**
     * Encode url
     *
     * @param mixed $s
     *
     * @return mixed
     */
    public function urlencode($s) {
        if ($s === false) {
            return $s;
        } else {
            return str_replace('%7E', '~', rawurlencode($s));
        }
    }
}
