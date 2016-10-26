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

namespace plagiarism_unplag\classes;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unplag_language
 *
 * @package plagiarism_unplag\classes
 * @subpackage  plagiarism
 * @namespace plagiarism_unplag\classes
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unplag.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unplag_language {

    /**
     * @var array
     */
    private static $supportedlanguage = array(
            'en' => 'en_EN',
            'es' => 'es_ES',
            'es_mx' => 'es_ES',
            'es_ve' => 'es_ES',
            'uk' => 'uk_UA',
            'nl' => 'nl_BE',
            'tr' => 'tr_TR',
            'fr' => 'fr_FR',
            'fr_ca' => 'fr_FR'
    );

    /**
     * @return array|bool|mixed
     */
    public static function get_unplag_language() {

        if (isset(self::$supportedlanguage[current_language()])) {
            $language = self::$supportedlanguage[current_language()];
        } else {
            $language = unplag_settings::get_settings('lang');
        }

        return $language;
    }

    /**
     * @param $url
     * @param int $showlangpicker
     */
    public static function inject_language_to_url(&$url, $showlangpicker = 0) {
        if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
            $language = self::get_unplag_language();
            $parsedurl = parse_url($url);

            if ($parsedurl) {
                $url = $parsedurl['scheme'] . '://' . $parsedurl['host'] . $parsedurl['path'];
                $slugs = array();
                if (!empty($parsedurl['query'])) {
                    parse_str(html_entity_decode($parsedurl['query']), $slugs);
                }
                $slugs['lang'] = $language;
                $slugs['show_lang_picker'] = $showlangpicker;
                $query = http_build_query($slugs);
                $url .= '?' . $query;
            }
        }
    }
}