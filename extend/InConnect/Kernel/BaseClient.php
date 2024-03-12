<?php


namespace InConnect\Kernel;


use InConnect\Kernel\Traits\CurlTrait;

class BaseClient
{
    use CurlTrait;

    protected $url = "https://ics.inhandiot.com";
    protected $app;
    protected $config;


    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
        $this->config = $this->app->getConfig();
        if (isset($this->config['international']) && $this->config['international'])
            $this->url = "https://ics.inhandnetworks.com/";
    }

    public function access_token()
    {
        
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
//        if ($options) {
//            $header[] = 'token:' . $token;
//        }
        $token = $this->signer($url, $method, $options);
        $header = array(
            'token:' . $token,
            'accessKey:' . $this->config['accessKey'],
            'timestamp:' . time(),
            'Content-Type:application/x-www-form-urlencoded;'
        );
        $response = $this->curl_request($url, $method, $options, $header);
        return $response;
    }
}