<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/8/5
 * Time: 14:07
 */

namespace WeChatPayV3\Payment\Kernel;


use WeChatPayV3\Kernel\Traits\CurlTrait;
use WeChatPayV3\Payment\Application;

class BaseClient
{
    use CurlTrait;
    /**
     * @var \WeChatPayV3\Payment\Application
     */
    protected $app;
    protected $config;


    /**
     * Constructor.
     *
     * @param \WeChatPayV3\Payment\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $this->app->getConfig();
    }

    /**
     * GET请求
     * @param $url
     * @param string $params
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpGet($url,string $params = '')
    {
        return $this->request($url,'GET',$params);
    }

    /**
     * POST请求
     * @param $url
     * @param array $params
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpPost($url,array $params)
    {
        return $this->request($url,'POST',$params);
    }

    /**
     * 加签与请求接口
     * @param string $url
     * @param string $method
     * @param array $options
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $url,string $method = 'GET',array $options = [])
    {
        $this->signer($url,$method,$options);
        $header = self::$defaults['headers'];
        $response = $this->curl_request($url,$method,$options,$header);
//        $response = $this->guzzle_request($url,$method,$options);
        return $response;

    }

}


