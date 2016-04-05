<?php

namespace plagiarism_unplag\library\OAuth\Signature;

/**
 * Class OAuthSignatureMethod
 * @package plagiarism_unplag\library\OAuth\Signature
 */
class OAuthSignatureMethod {
    public function check_signature(&$request, $consumer, $token, $signature) {
        $built = $this->build_signature($request, $consumer, $token);

        return $built == $signature;
    }
}