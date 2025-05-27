<?php


namespace InConnect\Kernel;


use InConnect\Kernel\Traits\AccessTokenTrait;
use InConnect\Kernel\Traits\CurlTrait;

class BaseClient
{
    use AccessTokenTrait;
    use CurlTrait;

    protected $url = "https://ics.inhandiot.com";
    protected $app;
    protected $config;
    protected $header = [
        "Content-Type: application/x-www-form-urlencoded; charset=utf-8",
    ];


    public function __construct(ServiceContainer $app, AccessTokenTrait $accessToken = null)
    {
        $this->app = $app;
        $this->config = $this->app->getConfig();
        if (isset($this->config['international']) && $this->config['international'])
            $this->url = "https://ics.inhandnetworks.com/";
    }

    /**
     * POST request.
     * @param string $url
     * @param array $data
     * @return bool|string
     */
    public function httpPost(string $url, array $data = [])
    {
        return $this->request($url, 'POST', $data);
    }

    /**
     * GET request.
     * @param string $url
     * @return bool|string
     */
    public function httpGet(string $url)
    {
        return $this->request($url, 'GET');
    }

    /**
     * 请求接口
     * @param string $url
     * @param string $method
     * @param array $options
     * @return bool|string
     */
    public function request(string $url, string $method = 'GET', array $options = [])
    {
        $header = $this->header;
        $header[] = "Authorization: Basic " . $this->config['private_key'];
        $response = $this->curl_request($url, $method, $options, $header);
        return $response;
    }
}