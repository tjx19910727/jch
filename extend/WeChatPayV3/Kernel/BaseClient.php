<?php


namespace WeChatPayV3\Kernel;


use WeChatPayV3\Kernel\GuzzleMiddleware\Util\AesUtil;
use WeChatPayV3\Kernel\Support\AesGcm;
use WeChatPayV3\Kernel\Traits\CurlTrait;

class BaseClient
{
    use CurlTrait;

    protected $app;
    protected $config;
    /**
     * @var string
     */
    protected $baseUri;

    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
        $this->config = $this->app->getConfig();
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

    public function request(string $url, string $method = 'GET', array $options = [])
    {
        $this->signer($url, $method, $options);
        self::$defaults['headers'][] = 'Wechatpay-Serial:' . $this->config['platform_serial'];
        $header = self::$defaults['headers'];
        $response = $this->curl_request($url, $method, $options, $header);
//        $response = $this->guzzle_request($url,$method,$options);
        return $response;
    }

    /**
     * 获取平台证书
     * @param $url
     * @return bool|string
     */
    public function getPlatformCertificate()
    {
        $url = '/v3/certificates';
        $this->signer($url, "GET", '');
        $header = self::$defaults['headers'];
        $list = $this->curl_request($url, "GET", '', $header);
        if (!isset($list['data'])) {
            return $list;
        }
        $decrypter = new AesUtil($this->config['v3_key']);
        foreach ($list['data'] as $item) {
            $encCert = $item['encrypt_certificate'];
            $plain = $decrypter->decryptToString($encCert['associated_data'],
                $encCert['nonce'], $encCert['ciphertext']);
            if (!$plain) {
                echo "encrypted certificate decrypt fail!\n";
                exit(1);
            }
            // 通过加载对证书进行简单合法性检验
            $cert = \openssl_x509_read($plain); // 从字符串中加载证书
            if (!$cert) {
                echo "downloaded certificate check fail!\n";
                exit(1);
            }
            $plainCerts[] = $plain;
            $x509Certs[] = $cert;
        }
        // 使用下载的证书再来验证一次应答的签名
//        $valid = ['header' => self::$header,'body' => $list];
//        dump($valid);
//        $validator = new WechatPay2Validator(new CertificateVerifier($x509Certs));
//        if (!$validator->curl_validate($valid)) {
//            echo "validate response fail using downloaded certificates!\n";
//            exit(1);
//        }
//        dump($this->config);
//        dump($this->config['cert_path']);
        $path = substr($this->config['cert_path'], 0, (strripos($this->config['cert_path'], "/") + 1));

        // 输出证书信息，并保存到文件
        foreach ($list['data'] as $index => $item) {
            $outPath = $path . 'wechatpay_' . $item['serial_no'] . '.pem';
            $outPaths[] = [
                'serial_no' => $item['serial_no'],
                'path' => $outPath,
            ];
            file_put_contents($outPath, $plainCerts[$index]);
        }
        return $outPaths;
    }

    /**
     * 解密
     * @param $ciphertext
     * @return string
     */
    public function decrypt($ciphertext)
    {
        return AesGcm::decrypt($ciphertext,$this->config['key']);
    }
}