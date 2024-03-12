<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/12
 * Time: 16:28
 */

namespace app\http\controller;


use app\BaseController;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\UserCredentials;
use OAuth2\Request;
use OAuth2\Response;
use OAuth2\Server;
use OAuth2\Storage\Pdo;

class Oauth2 extends BaseController
{
    protected function getOAuthServer()
    {
        $dsn = "";
        $username = "";
        $password = "";
        $config = [
            "user_table" => "",
            "client_table" => "",
            "access_token_table" => "",
            "scope_table" => "",
        ];
        $pdo = new \PDO($dsn,$username,$password);
        $pdoStorage = new Pdo($pdo,$config);

        $server = new Server($pdoStorage);
        // 配置客户端身份验证
        $server->addGrantType(new ClientCredentials($pdoStorage));
        // 配置用户身份验证
        $server->addGrantType(new UserCredentials($pdoStorage));
        return $server;
    }
    public function authorizeAction()
    {
        $server = $this->getOAuthServer();
        $response = new Response();
        if ($server->validateAuthorizeRequest(Request::createFromGlobals(),$response)) {
            return "授权失败";
        }
        if (\request()->isPost()) {
            $is_authorized = (bool) input("authorized",'');
            $server->handleAuthorizeRequest(Request::createFromGlobals(),$response,$is_authorized);
            return $response;
        }
        return "打开授权页面";
    }

    public function tokenAction()
    {
        $server = $this->getOAuthServer();
        $response = new Response();
        $server->handleTokenRequest(Request::createFromGlobals(),$response);
        return $response;
    }

    public function refreshAction()
    {
        $server = $this->getOAuthServer();
        $response = new Response();
        $server->handleTokenRequest(Request::createFromGlobals(),$response);
        return $response;
    }
}