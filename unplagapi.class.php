<?php

Class UnApi
{
    private $apiSecret;
    private $apiKey;
    private $apiUrl = UNPLAG_DOMAIN.'api';

    function __construct($apiKey = false, $apiSecret = false){
        if (!$apiKey || !$apiSecret)
            throw new Exception('apiKey and apiSecret must be provided');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function _call($method, $params = false){
        if ($params && !is_array($params))
            throw new Exception('$params must be an array');
        $params['ClientID'] = $this->apiKey;
        $real_post = array();
        $real_post['json'] = json_encode($params);
        $real_post['sign'] = md5($real_post['json'] . $this->apiSecret);
        $postdata = http_build_query($real_post);
        //log_message(3, $postdata);
        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents($method, false, $context);
        $res = json_decode(trim($result), true);
        return ($res)?$res:$result;
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