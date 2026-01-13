<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/01/05
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\WeiCheng;

use app\AppFactory\Kernel\Traits\WeiCheng\WcGoodsTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcGoodsTypesTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcRequestLogsTrait;

trait WcBaseTrait
{
    use WcGoodsTrait, WcGoodsTypesTrait, WcRequestLogsTrait;
    
    public function initWcBase()
    {
        $this->configType = "weicheng";
        if(env("CglPay.is_test")){
            $this->configType = "weichengTest";
            $this->config = [
                "distributor_id" => "520253",
                "apikey" => "ab50e9d1038e4905b1d5f1f263e69e18_n",
                "apisecret" => "d1e79b35bc6f491993f873c56b163f47",
                "secretkey" => "8f8d4818c49f44e6bb53d04b",
                "apiDomain" => "https://test-admin.weicheng.jchtechnologies.com",
            ];
        }else{
            $this->configType = "weicheng";
            $this->config = [
                "distributor_id" => "520253",
                "apikey" => "ab50e9d1038e4905b1d5f1f263e69e18_n",
                "apisecret" => "d1e79b35bc6f491993f873c56b163f47",
                "secretkey" => "8f8d4818c49f44e6bb53d04b",
                "apiDomain" => "https://test-admin.weicheng.jchtechnologies.com",
            ];
        }
        
        $this->goods_sync_url = $this->config['apiDomain']."/api/goods/sync";
        $this->order_add_url = $this->config['apiDomain']."/api/order/add";
        $this->order_refund_url = $this->config['apiDomain']."/api/order/refund";
        $this->order_detail_url = $this->config['apiDomain']."/api/order/detail";
        $this->order_refundPart_url = $this->config['apiDomain']."/api/order/refundPart";
        $this->get_sms_code_url = "https://api.weicheng.jchtechnologies.com/msvc-shop/v1/mp/user/phone/send/code";
        $this->phone_login_url = "https://api.weicheng.jchtechnologies.com/msvc-shop/v1/mp/user/phoneLogin";
        $this->user_sync_points = "https://api.weicheng.jchtechnologies.com/msvc-shop/v1/mp/user/syncIntegral";
    }

    public function getDecptData($data)
    {
        $key = $this->config['secretkey'];
        $data_json = json_encode($data);
        return strtoupper(bin2hex(openssl_encrypt($data_json, 'des-ede3', $key, OPENSSL_RAW_DATA)));
        // return $encryptData;
        // echo $encryptData."\n";
        // $decryptData = openssl_decrypt(hex2bin($encryptData), 'des-ede3', $key, OPENSSL_RAW_DATA);
        // echo $decryptData."\n";
    }

    //数据签名，用于验证请求是否合法，使用md5签名(apisecret+3des加密前的数据+apisecret)
    public function getSign($data){
        $key = $this->config['apisecret'];
        $data_json = json_encode($data);
        return md5($key . $data_json . $key);
    }

    
    public function weicheng_curl($url, $postFields, $header = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        if($header){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }else{
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        }
        //todo   上线后需删除   方便本地调用https接口
        if(strstr(php_uname('s'), 'Windows')){
            curl_setopt($ch,CURLOPT_CAINFO, "D:\phpstudy_pro\wwwroot\backend\public\static\cacert.pem");
        }
        $response = curl_exec($ch);
        if(curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $this->addWcRequestLogs([
            'request_url' => $url,
            'request_header' => $header ? json_encode($header) : json_encode(['Content-Type: application/x-www-form-urlencoded'], JSON_UNESCAPED_UNICODE),
            'request_body' => json_encode($postFields, JSON_UNESCAPED_UNICODE),
            'response_body' => $response,
            'response_status' => $status,
            'create_time' => time(),
            'type' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return ['response' => $response, 'status' => $status];
    }

    public function goodsSync($goods_no){
        $this->initWcBase();
        $data = [
            'distributor_id' => $this->config['distributor_id'],
            'goods_no' => $goods_no,
        ];
        $postUrl = $this->goods_sync_url."?apikey=".$this->config['apikey']."&sign=".$this->getSign($data)."&data=".$this->getDecptData($data);
        $response = $this->weicheng_curl($postUrl, []);
        return $response;
    }

    public function synchronizeGoods2Db($response)
    {
        $updateData = json2arr($response);
        $updateData = $updateData['product'];
        $updateData['resourcesArray'] = json_encode($updateData['resourcesArray'], JSON_UNESCAPED_UNICODE);
        $wc_goods = $this->getWcGoodsFind(['no' => $updateData['no']]);
        if(!$wc_goods){
            $updateData['created_at'] = date('Y-m-d H:i:s');
            $this->addWcGoods($updateData);
        } else {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->updateWcGoods($updateData,['no' => $updateData['no']]);
        }
        return true;
    }

    public function getSmsCode($phone, $machine_id){
        $this->initWcBase();
        $postUrl = $this->get_sms_code_url."?phone=".$phone."&machine_code=".$machine_id;
        return $this->weicheng_curl($postUrl, []);
    }


    public function wcLoginUser($phone, $machine_id, $code){
        $this->initWcBase();
        $postUrl = $this->phone_login_url."?phone=".$phone."&machine_code=".$machine_id."&code=".$code;
        return $this->weicheng_curl($postUrl, []);
    }

    public function wcUserSyncPoints($token, $integral, $op_type){
        $this->initWcBase();
        $postUrl = $this->user_sync_points."?op_type=".$op_type."&integral=".(int)$integral;
        $header = array('token: '.$token);
        return $this->weicheng_curl($postUrl, [], $header);
    }
}
