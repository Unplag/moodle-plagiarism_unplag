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
 * unplagapi.class.php - SDK for working with unplag api.
 *
 * @package   plagiarism_unplag
 * @author     Mikhail Grinenko <m.grinenko@p1k.co.uk>
 * @copyright  UKU Group, LTD, https://www.unplag.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unplag\classes;

Class UnApi
{
    private $apiSecret;
    private $apiKey;
    private $apiUrl = 'https://unplag.com/api';

    function __construct($apiKey = false, $apiSecret = false){
        if (!$apiKey || !$apiSecret)
            throw new Exception('apiKey and apiSecret must be provided');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    private function _get_contents($method, $real_post){
         $postdata = http_build_query($real_post);

            $opts = array('http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $postdata
                )
            );

            $context  = stream_context_create($opts);

           
            return file_get_contents($method, false, $context);
    }
    
    private function _curl_get_contents($method, $real_post){
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $method);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($real_post));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_VERBOSE, true);

        $response = curl_exec($curl);
        $info = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_errno($curl);
        curl_close($curl);
        
        return $response;
    }
    
    public function _call($method, $params = false){
            
            if($params && !is_array($params))
                throw new Exception('$params must be an array');
        
            $params['ClientID'] = $this->apiKey;
            $real_post = array();
            $real_post['json'] = json_encode($params);
            $real_post['sign'] = md5($real_post['json'].$this->apiSecret);
           
            if(function_exists('curl_init')){
                $result = $this->_curl_get_contents($method, $real_post);
            }
            else{
                $result = $this->_get_contents($method, $real_post);
            }
            $res = json_decode(trim($result), true);  
            
            return $res;  
    }

    public function UploadFile($ext, $f_content, $owner_email = false){
        $url = $this->apiUrl . '/UploadFile';
        $postdata = array('ext' => $ext, 'f_content' => base64_encode($f_content), 'owner_email' => $owner_email);
        return $this->_call($url, $postdata);
    }

    public function Check($type, $file_id = array(), $callback_url = false, $owner_email = false){
        $url = $this->apiUrl . '/Check';
        $postdata = array('type' => $type, 'file_id' => $file_id, 'callback' => $callback_url, 'owner_email' => $owner_email);
        return $this->_call($url, $postdata);
    }

    public function GetResults($check_id){
        $url = $this->apiUrl . '/GetResults';
        $postdata = array('check_id' => $check_id);
        return $this->_call($url, $postdata);
    }

    public function CalculateCost($type, $file_id = array(), $words_count){
        $url = $this->apiUrl . '/CalculateCost';
        $postdata = array('type' => $type, 'file_id' => $file_id, 'words_count' => $words_count);
        return $this->_call($url, $postdata);
    }
}