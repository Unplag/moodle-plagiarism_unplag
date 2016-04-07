<?php

namespace plagiarism_unplag\library\OAuth\Signature;

use plagiarism_unplag\library\OAuth\OAuthRequest;

/**
 * Class OAuthSignatureMethod
 * @package plagiarism_unplag\library\OAuth\Signature
 */
abstract class OAuthSignatureMethod {
    /**
     * @param $request
     * @param $consumer
     * @param $token
     * @param $signature
     *
     * @return bool
     */
    public function check_signature(&$request, $consumer, $token, $signature) {
        $built = $this->build_signature($request, $consumer, $token);

        return $built == $signature;
    }

    /**
     * @param OAuthRequest $request
     * @param              $consumer
     * @param              $token
     *
     * @return mixed
     */
    abstract public function build_signature(OAuthRequest $request, $consumer, $token);
}