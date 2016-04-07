<?php

namespace plagiarism_unplag\library\OAuth\Signature;

use plagiarism_unplag\library\OAuth\OAuthRequest;
use plagiarism_unplag\library\OAuth\OAuthUtil;

/**
 * Class OAuthSignatureMethod_HMAC_SHA1
 * @package plagiarism_unplag\library\OAuth\Signature
 */
class OAuthSignatureMethod_HMAC_SHA1 extends OAuthSignatureMethod {
    /**
     * @return string
     */
    function get_name() {
        return "HMAC-SHA1";
    }

    /**
     * @param $request
     * @param $consumer
     * @param $token
     *
     * @return string
     */
    public function build_signature(OAuthRequest $request, $consumer, $token) {
        global $oauth_last_computed_signature;
        $oauth_last_computed_signature = false;

        $base_string = $request->get_signature_base_string();
        $request->base_string = $base_string;

        $key_parts = [
            $consumer->secret,
            ($token) ? $token->secret : "",
        ];

        $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
        $key = implode('&', $key_parts);

        $computed_signature = base64_encode(hash_hmac('sha1', $base_string, $key, true));
        $oauth_last_computed_signature = $computed_signature;

        return $computed_signature;
    }
}