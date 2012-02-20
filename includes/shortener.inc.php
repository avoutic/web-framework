<?php

abstract class ShortenerCore
{
    protected $database;

    function __construct($database)
    {
        $this->database = $database;
    }

    abstract function shorten($url);
    abstract function expand($url);
};

class GoogleShortener extends ShortenerCore
{
    protected $api_url;

    function __construct($database, $api_key)
    {
        parent::__construct($database);

        $this->api_url = 'https://www.googleapis.com/urlshortener/v1/url?key='.$api_key;
    }

    function shorten($url)
    {
        $response = $this->send($url);

        return isset($response['id']) ? $response['id'] : FALSE;
    }

    function expand($url)
    {
        $response = $this->send($url, false);
        
        return isset($response['longUrl']) ? $response['longUrl'] : FALSE;
    }

    function send($url, $shorten = true)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($shorten)
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_URL, $this->api_url);
            curl_setopt($curl, CURLOPT_POSTFIELDS,
                        json_encode(array(
                            "longUrl" => $url)));
            curl_setopt($curl, CURLOPT_HTTPHEADER,
                        array("Content-Type: application/json"));
        }
        else
        {
            $long_url = $this->api_url.'&shortUrl='.$url;
            curl_setopt($curl, CURLOPT_URL, $long_url);
        }

        $result = curl_exec($curl);
        curl_close($curl);

        return json_decode($result, true);
    }
};
