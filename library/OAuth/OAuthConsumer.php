<?php

namespace plagiarism_unplag\library\OAuth;

/**
 * Class OAuthConsumer
 * @package plagiarism_unplag\library\OAuth
 */
class OAuthConsumer {
    public $key;
    public $secret;

    /**
     * OAuthConsumer constructor.
     *
     * @param      $key
     * @param      $secret
     * @param null $callback_url
     */
    function __construct($key, $secret, $callback_url = null) {
        $this->key = $key;
        $this->secret = $secret;
        $this->callback_url = $callback_url;
    }

    /**
     * @return string
     */
    function __toString() {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }
}