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
 * OAuthSignatureMethod.class.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\library\OAuth\Signature;

use plagiarism_unplag\library\OAuth\OAuthRequest;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class OAuthSignatureMethod
 *
 * @package     plagiarism_unplag
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class OAuthSignatureMethod {
    /**
     * Check signature
     *
     * @param OAuthRequest $request
     * @param object       $consumer
     * @param mixed        $token
     * @param string       $signature
     *
     * @return bool
     */
    public function check_signature(OAuthRequest &$request, $consumer, $token, $signature) {
        $built = $this->build_signature($request, $consumer, $token);

        return $built == $signature;
    }

    /**
     * Build signature
     *
     * @param OAuthRequest $request
     * @param object       $consumer
     * @param mixed        $token
     *
     * @return mixed
     */
    abstract public function build_signature(OAuthRequest $request, $consumer, $token);

    /**
     * Get method name
     *
     * @return string
     */
    abstract public function get_name();
}