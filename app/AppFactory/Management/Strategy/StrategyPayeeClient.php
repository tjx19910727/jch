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
            $data = [];
            if (isset($config['serial']) && $config['serial']) {
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
            }
            return $this->r(200, '获取成功', $data);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    public function exportPayee($where)
    {
        $list = $this->getStrategyPayeeList($where,0,"*","sp_id desc");

        if(is_null($list) || $list->isEmpty()){
            return $this->rFail("没有数据可导出");

        }
        $payType = config('payment.strategy_payee_type_map') ?: [];
        $list = $list->toArray();
        foreach ($list as &$item) {
            $item['content'] = json2arr($item['content']);
            $item["organization_name"] = $item["organization_name"] ?: "未知";
            $item["customer_num"] = $item['content']["customerNum"] ??"未知";
            $item['payee_type_text'] = $payType[$item['payee_type']] ?? "未知";
            $item['status_text'] = $item['status'] == 1 ? "启用" : "禁用";
        }
        $title = [
            "sp_name" => "策略名称",
            "payee_type_text" => "收款类型",
            "organization_name" => "组织名称",
            "customer_num" => "商户编号",
            "mch_id" => "商户ID",
            "app_id" => "应用ID",
            "status_text" => "状态",
        ];
        $filename = "收款策略-" . date("YmdHis");
        return $this->sendToExport("收款策略-报表", $filename, $title, $list);
    }
}
