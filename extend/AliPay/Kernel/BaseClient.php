<?php


namespace AliPay\Kernel;


define('ALI_ROOT_PATH', str_replace( '\\' , '/' , realpath(dirname(__FILE__).'/../')));

class BaseClient
{
    protected $app;
    protected $config;
    protected $bizContent;
    protected $onceName;
    /**
     * @var \AopClient|\AopCertClient
     */
    protected $aop;
    protected $notifyUrl = "";
    /**
     * @var string
     */
    protected $baseUri;

    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
        $this->config = $this->app->getConfig();
        if ($this->config) {
            $rootPath = root_path();
            strpos($this->config['private_key_path'],$rootPath) !== false ? : $this->config['private_key_path'] = $rootPath . "public" . $this->config['private_key_path'];
            strpos($this->config['ali_public_key_path'],$rootPath) !== false ? : $this->config['ali_public_key_path'] = $rootPath . "public" . $this->config['ali_public_key_path'];
            strpos($this->config['ali_root_cert_path'],$rootPath) !== false ? : $this->config['ali_root_cert_path'] = $rootPath . "public" . $this->config['ali_root_cert_path'];
            strpos($this->config['app_public_key_path'],$rootPath) !== false ? : $this->config['app_public_key_path'] = $rootPath . "public" . $this->config['app_public_key_path'];
            $this->config['isObject'] = false;
        }
        !isset($this->config['notifyUrl']) ?: $this->notifyUrl = $this->config['notifyUrl'];
    }

    /**
     * 引用
     * @return mixed
     */
    public function requireOnce()
    {
        return require_once ALI_ROOT_PATH . "/aop/request/{$this->onceName}.php";
    }

    public function setBizContent($bizContent)
    {
        $this->bizContent = $bizContent;
        return $this;
    }

    /**
     * 自定义引用接口，需要配合setBizContent方法设置参数，再调用execute方法发起请求
     * @param $apiName
     */
    public function customApi($apiName)
    {
        $name_arr = explode(".", $apiName);
        $str = "";
        foreach ($name_arr as $v) {
            $str .= ucfirst($v);
        }
        $this->onceName;
        $this->requireOnce();
        return $this;
    }

    /**
     * 实例化参数
     * @return mixed
     */
    public function newRequest()
    {
        $this->requireOnce();
        return new $this->onceName();
    }

    /**
     * 发起接口请求
     * @return mixed
     */
    public function execute()
    {
        $this->AopCert($this->config);
        $request = $this->newRequest();
        !$this->bizContent ?: $request->setBizContent(json_encode($this->bizContent, JSON_UNESCAPED_UNICODE));
        !$this->notifyUrl ?: $request->setNotifyUrl($this->notifyUrl);
        return $this->returnExecute($this->aop->execute($request), $request);
    }

    /**
     * 返回接口返回数据
     * @param $result
     * @param $request
     * @return mixed
     */
    public function returnExecute($result, $request)
    {
        $requestNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $return = isset($result->$requestNode) ? $result->$requestNode : $result->error_response;
        if (isset($this->config['isObject']) && !$this->config['isObject']) {
            return json_decode(json_encode($return), true);
        }
        return $return;
    }

    /**
     * 网站类发起请求
     * @return mixed
     */
    public function pageExecute()
    {
        $this->AopCert($this->config);
        $request = $this->newRequest();
        !$this->bizContent ?: $request->setBizContent(json_encode($this->bizContent, JSON_UNESCAPED_UNICODE));
        !$this->notifyUrl ?: $request->setNotifyUrl($this->notifyUrl);
        return $this->aop->pageExecute($request);
    }

    /**
     * 证书初始化
     * @param $config
     * @return \AopCertClient
     */
    public function AopCert($config)
    {
        require_once ALI_ROOT_PATH . '/aop/AopCertClient.php';
        $aop = new \AopCertClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = $config['app_id'];
//        $aop->rsaPrivateKey = $config['private_key'];
        $aop->rsaPrivateKeyFilePath = $config['private_key_path'];
        $aop->format = "json";
        $aop->postCharset = "utf-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $aop->getPublicKey($config['app_public_key_path']);
        $aop->isCheckAlipayPublicCert = true;
        $aop->appCertSN = $aop->getCertSN($config['app_public_key_path']);
        $aop->alipayRootCertSN = $aop->getRootCertSN($config['ali_root_cert_path']);
        return $this->aop = $aop;
    }

    /**
     * 公钥初始化
     */
    public function Aop($config)
    {
        require_once ALI_ROOT_PATH . '/aop/AopClient.php';
        require_once ALI_ROOT_PATH . '/aop/AlipayConfig.php';
        $aliConfig = new \AlipayConfig();
        $aliConfig->setServerUrl("https://openapi.alipay.com/gateway.do");
        $aliConfig->setAppId($config['app_id']);
        $aliConfig->setPrivateKey($config['private_key']);
        $aliConfig->setFormat("json");
        $aliConfig->setCharset("UTF-8");
        $aliConfig->setSignType("RSA2");
        $aliConfig->setAlipayPublicKey($config['ali_public_key']);
        $aop = new \AopClient($aliConfig);
        return $this->aop = $aop;
    }

    /**
     * 解密字符串
     * @param $str
     * @return mixed
     */
    public function decryptStr($str)
    {
        require_once ALI_ROOT_PATH . '/aop/AopEncrypt.php';
        $result = openssl_decrypt(base64_decode($str), 'AES-128-CBC', base64_decode($this->config['aes_key']), OPENSSL_RAW_DATA);
        return json_decode($result, true);
    }
}