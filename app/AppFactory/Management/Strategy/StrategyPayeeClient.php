<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:39
 */

namespace app\AppFactory\Management\Strategy;


use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Management\ManagementClient;
use WeChatPayV3\Factory;

class StrategyPayeeClient extends ManagementClient
{
    use StrategyPayeeTrait,StrategyMachineTrait;

    /**
     * 获取微信平台证书
     * @param $sp_id
     * @return array|string
     */
    public function getWxPlatformCert($sp_id)
    {
        try {
            $config = $this->getStrategyPayeeFind(['sp_id' => $sp_id]);
            if (!$config) return $this->r(100, '查无配置数据');
            $config = $config->toArray();//        $check = $this->validate($config,'app\system\validate\Wechat_pay.v3');
            if ($config['payee_type'] != 1) return $this->r(100,'微信才需要获取平台证书');
            $config = array_merge($config, json2arr($config['content']));//        if ($check !== true) return returnValidate($check);
            $config['cert_path'] = $this->getUrl($config['cert_path']);//        $config['cert_path'] = ROOT_PATH . "public" . $config['cert_path'];
            $config['key_path'] = $this->getUrl($config['key_path']);//        $config['key_path'] = ROOT_PATH . "public" . $config['key_path'];
            $config = [
                'mchid' => $config['mch_id'],
                'serial' => $config['serial'],
                'privateKey' => $config['key_path'],
                'certs' => [$config['serial'] => file_get_contents($config['cert_path'])],
                'cert_path' => $config['cert_path'],
                'v3_key' => $config['v3_key'],
            ];
            $app = Factory::payment($config);//        $app = \WeChatPayV3\Factory::payment($config);
            $cert = $app->transfer->getPlatformCertificate();
            if (isset($cert['code']) && isset($cert['message'])) {
                return $this->r(100, $cert['message'], $cert);
            }
            $data = [
                'platform_serial' => $cert[0]['serial_no'],
                'platform_path' => $cert[0]['path'],
                'platform_update_time' => time(),
            ];
            return $this->r(200, '获取成功', $data);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }
}