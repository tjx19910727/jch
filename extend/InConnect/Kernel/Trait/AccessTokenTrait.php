<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/12
 * Time: 16:18
 */

namespace InConnect\Kernel\Traits;


trait AccessTokenTrait
{
    public $access_token;
    protected $cacheKey = "InConnectionAccessTokenArr";
    protected $cache_time = 86400 * 15;
    protected $tokenConfig = [
        "username" => "",
        "password" => "",
        "grant_type" => "password",
        "client_id" => "000017953450251798098136",
        "client_secret" => "08E9EC6793345759456CB8BAE52615F3",
    ];



    public function getAccessToken(bool $refresh_token = false)
    {
        $header = $this->header;
        $header[] = "Authorization: Basic " . $this->config['private_key'];
        $url = $this->url . "/oauth2/access_token";
        $this->tokenConfig['password_type'] = 2;
        $params = http_build_query($this->tokenConfig);
        $this->curl_request($url,"POST",$params,$header);
    }

    private function refreshToken()
    {
        $header = $this->header;
        $header[] = "Authorization: Basic " . $this->config['private_key'];
        $url = $this->url . "/oauth2/access_token";
        $params = [
            "grant_type" => "refresh_token",
            "refresh_token" => $this->access_token['refresh_token'],
        ];
        $params = http_build_query($params);
        $this->curl_request($url,"POST",$params,$header);
    }

    private function getCacheToken()
    {
        return ;
    }
}