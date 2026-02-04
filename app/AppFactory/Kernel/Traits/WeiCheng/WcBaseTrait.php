<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/01/05
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\WeiCheng;

use app\AppFactory\Kernel\Traits\WeiCheng\WcGoodsTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcRequestLogsTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;

trait WcBaseTrait
{
    use WcGoodsTrait,  WcRequestLogsTrait, SaleOrdersTrait;

    public function initWcBase()
    {
        $this->configType = "weicheng";
        if (env("CglPay.is_test")) {
            $this->configType = "weichengTest";
            $this->config = [
                "distributor_id" => "520253",
                "apikey" => "ab50e9d1038e4905b1d5f1f263e69e18_n",
                "apisecret" => "d1e79b35bc6f491993f873c56b163f47",
                "secretkey" => "8f8d4818c49f44e6bb53d04b",
                "apiDomain" => "https://test-admin.weicheng.jchtechnologies.com",
            ];
        } else {
            $this->configType = "weicheng";
            $this->config = [
                "distributor_id" => "520253",
                "apikey" => "ab50e9d1038e4905b1d5f1f263e69e18_n",
                "apisecret" => "d1e79b35bc6f491993f873c56b163f47",
                "secretkey" => "8f8d4818c49f44e6bb53d04b",
                "apiDomain" => "https://test-admin.weicheng.jchtechnologies.com",
            ];
        }

        $this->goods_type_sync_url = $this->config['apiDomain'] . "/api/goods/typeSync";
        $this->goods_sync_url = $this->config['apiDomain'] . "/api/goods/sync";
        $this->order_add_url = $this->config['apiDomain'] . "/api/order/add";
        $this->order_refund_url = $this->config['apiDomain'] . "/api/order/refund";
        $this->order_detail_url = $this->config['apiDomain'] . "/api/order/detail";
        $this->order_refundPart_url = $this->config['apiDomain'] . "/api/order/refundPart";
        $this->get_sms_code_url = "https://api.weicheng.jchtechnologies.com/msvc-shop/v1/mp/user/phone/send/code";
        $this->phone_login_url = "https://api.weicheng.jchtechnologies.com/msvc-shop/v1/mp/user/phoneLogin";
        $this->user_sync_points = "https://api.weicheng.jchtechnologies.com/msvc-shop/v1/mp/user/syncIntegral";
        $this->get_points_qrcode = "https://api.weicheng.jchtechnologies.com/msvc-shop/v1/mp/user/getIntegralQrcode";
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
    public function getSign($data)
    {
        $key = $this->config['apisecret'];
        $data_json = json_encode($data);
        return md5($key . $data_json . $key);
    }


    public function weicheng_curl($url, $postFields = [], $header = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        }
        //todo   上线后需删除   方便本地调用https接口
        if (strstr(php_uname('s'), 'Windows')) {
            curl_setopt($ch, CURLOPT_CAINFO, "D:\phpstudy_pro\wwwroot\backend\public\static\cacert.pem");
        }
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->addWcRequestLogs([
            'request_url' => $url,
            'request_headers' => $header ? json_encode($header) : json_encode(['Content-Type: application/x-www-form-urlencoded'], JSON_UNESCAPED_UNICODE),
            'request_body' => json_encode($postFields, JSON_UNESCAPED_UNICODE),
            'response_body' => $response,
            'response_status' => $status,
            'type' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return ['response' => $response, 'status' => $status];
    }

    public function goodsTypesSync($goods_type, $nowPage = 1, $pageSize = 100)
    {
        $this->initWcBase();
        $data = [
            'distributor_id' => $this->config['distributor_id'],
            'type' => $goods_type,
            'pageSize' => $pageSize,
            'nowPage' => $nowPage,
        ];
        $postUrl = $this->goods_type_sync_url . "?apikey=" . $this->config['apikey'] . "&sign=" . $this->getSign($data) . "&data=" . $this->getDecptData($data);
        return $this->weicheng_curl($postUrl, []);
    }

    public function synchronizeGoodsLists2Db($goods_lists, $type)
    {
        foreach ($goods_lists as $goods) {
            $wc_goods = $this->getWcGoodsFind(['no' => $goods['no']]);
            $goods['type'] = $type;
            if (!$wc_goods) {
                $this->addWcGoods($goods);
            } else {
                $this->updateWcGoods($goods, ['no' => $goods['no']]);
            }
        }
        return true;
    }

    public function goodsSync($goods_no, $type)
    {
        $this->initWcBase();
        $data = [
            'distributor_id' => $this->config['distributor_id'],
            'goods_no' => $goods_no,
            'type' => $type,
        ];
        $postUrl = $this->goods_sync_url . "?apikey=" . $this->config['apikey'] . "&sign=" . $this->getSign($data) . "&data=" . $this->getDecptData($data);
        return $this->weicheng_curl($postUrl, []);
    }

    public function synchronizeGoods2Db($updateData)
    {
        $wc_goods = $this->getWcGoodsFind(['no' => $updateData['no']]);
        if (!$wc_goods) {
            $this->addWcGoods($updateData);
        } else {
            $this->updateWcGoods($updateData, ['no' => $updateData['no']]);
        }
        return true;
    }

    //微程拉取的商品本地化存储
    //$no为wc_goods表的no,wc_goods_local表分外部no和子商品no
    public function setWcGoodsLocal($no, $type = 0)
    {
        $wc_goods_type = $this->getWcGoodsTypesList([['id', '>', '0']])->toArray();
        $wc_goods_type_arr = [];
        foreach ($wc_goods_type as $v) {
            $wc_goods_type_arr[$v['id']] = $v['name'];
        }

        $wc_goods = $this->getWcGoodsFind(['no' => $no])->toArray();
        $resourceDomain = $wc_goods['resourceDomain'];
        if (!is_null($wc_goods['goods'])) {
            //子商品信息
            $goods = json_decode($wc_goods['goods'], true);
            foreach ($goods as $good) {
                $wc_goods_local = $this->getWcGoodsLocalFind(['no' => $good['no'],'out_no' => $wc_goods['no']]);
                $setData = [
                    'g_id' => $good['g_id'] ?? '',
                    'out_no' => $no ?? '',
                    'no' => $good['no'] ?? '',
                    'type' => $type,
                    'g_name' => $good['name'] ?? '',
                    'g_type' => $good['type'] ?? 0,
                    'g_type_name' => $wc_goods_type_arr[$good['type']] ?? '',
                    'retail_price' => $good['price'] ?? '',
                    'pic' => $good['main_img'] ? $resourceDomain . $good['main_img'] : '',
                    'sell_channel' => 3,
                    'desc' => $good['notice'] ?? '',
                    'status' => 1,
                    'channel_code' => 'Z10',
                ];
                if (!$wc_goods_local) {
                    $this->addWcGoodsLocal($setData);
                } else {
                    $this->updateWcGoodsLocal($setData, ['out_no' => $no]);
                }
            }
        }
        if (!is_null($wc_goods['combination_goods'])) {
            $combination_goods = json_decode($wc_goods['combination_goods'], true) ?? [];
            foreach ($combination_goods as $combind_good) {
                $combindSetData = [
                    'g_id' => $good['g_id'] ?? '',
                    'out_no' => $no ?? '',
                    'no' => $combind_good['no'] ?? '',
                    'type' => $type,
                    'g_name' => $combind_good['name'] ?? '',
                    'g_type' => $combind_good['type'] ?? 0,
                    'g_type_name' => $wc_goods_type_arr[$combind_good['type']] ?? '',
                    'retail_price' => $combind_good['price'] ?? '',
                    'pic' => $combind_good['main_img'] ? $resourceDomain . $combind_good['main_img'] : '',
                    'sell_channel' => 3,
                    'desc' => $combind_good['notice'] ?? '',
                    'status' => 1,
                    'channel_code' => 'Z10',
                ];
                $wc_goods_local = $this->getWcGoodsLocalFind(['no' => $combindSetData['no'],'out_no' => $wc_goods['no']]);
                if (!$wc_goods_local) {
                    $this->addWcGoodsLocal($combindSetData);
                } else {
                    $this->updateWcGoodsLocal($combindSetData, ['out_no' => $no]);
                }
            }
        }

        return true;
    }

    public function getSmsCode($phone, $machine_id)
    {
        $this->initWcBase();
        $postUrl = $this->get_sms_code_url . "?phone=" . $phone . "&machine_code=" . $machine_id;
        return $this->weicheng_curl($postUrl);
    }


    public function wcLoginUser($phone, $machine_id, $code)
    {
        $this->initWcBase();
        $postUrl = $this->phone_login_url . "?phone=" . $phone . "&machine_code=" . $machine_id . "&code=" . $code;
        return $this->weicheng_curl($postUrl);
    }

    public function wcUserSyncPoints($token, $integral, $op_type)
    {
        $this->initWcBase();
        $postUrl = $this->user_sync_points . "?op_type=" . $op_type . "&integral=" . (int)$integral;
        $header = array('token: ' . $token);
        return $this->weicheng_curl($postUrl, [], $header);
    }


    public function wcPointsQrCode($integral)
    {
        $this->initWcBase();
        $postUrl = $this->get_points_qrcode . "?integral=" . (int)$integral;
        return $this->weicheng_curl($postUrl);
    }

    public function orderSync2Wc($order)
    {
        $details = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']]);
        if (!$details) return true;
        $details = $details->toArray();
        foreach ($details as $detail) {
            $wc_order_no = $this->orderDetailSync2Wc($order, $detail);
            $wc_order_no_json = json_encode($wc_order_no);
            $this->updateSaleOrdersDetails(['wc_order_no' => $wc_order_no_json], ['sod_id' => $detail['sod_id']]);
        }
        //返回true是为了不影响正常出货流程
        return true;
    }

    public function orderDetailSync2Wc($order, $detail)
    {
        $this->initWcBase();
        $wc_machine_channel = $this->getWcMachineChannelFind(['mc_id' => $detail['mc_id']]);
        if (!$wc_machine_channel) return true;
        $wc_machine_channel = $wc_machine_channel->toArray();

        $wc_goods_locals = $this->getWcGoodsLocalList(['out_no' => $wc_machine_channel['out_no']]);
        if (!$wc_goods_locals) return true;
        $wc_goods_locals = $wc_goods_locals->toArray();
        $wc_order_no = [];
        foreach ($wc_goods_locals as $wc_goods_local) {
            $data = [
                'out_order_no' => $order['trade_no'] . '#' . $detail['sod_id'],
                'goods_no' => $wc_goods_local['no'],
                'goods_quantity' => $detail['quantity'],
                'link_man' => '会员',
                'link_phone' => $order['mobile'] ?: '',
                'identity_card' => '',
                'link_address' => '',
                'link_remark' => '',
                'trip_date' => date('Y-m-d'),
                'distributor_id' => $this->config['distributor_id'],
            ];
            $postUrl = $this->order_add_url . "?apikey=" . $this->config['apikey'] . "&sign=" . $this->getSign($data) . "&data=" . $this->getDecptData($data);
            $res = $this->weicheng_curl($postUrl, []);
            // $res['response'] = '{"order_no":"O745770017646077","orderNo":"O745770017646077","tickets":[],"ticket_check_style":0,"tip":"出库成功","status":"success"}';
            $res_arr = json_decode($res['response'], true);
            if ($res_arr['status'] == "fail") actionLog($detail, "子订单同步失败" . $res_arr['tip']);
            $wc_order_no[$wc_goods_local['no']] = $res_arr['order_no'];
        }
        return $wc_order_no;
    }

    public function orderRefundSync2Wc($order_no, $out_out_no)
    {
        $this->initWcBase();
        $data = [
            'distributor_id' => $this->config['distributor_id'],
            'order_no' => $order_no,
            'out_out_no' => $out_out_no,
        ];
        $postUrl = $this->order_refund_url . "?apikey=" . $this->config['apikey'] . "&sign=" . $this->getSign($data) . "&data=" . $this->getDecptData($data);
        return $this->weicheng_curl($postUrl, []);
    }


    public function orderRefundPartSync2Wc($refund_no, $order_no, $out_order_no, $result)
    {
        $this->initWcBase();
        $data = [
            'method' => 'refundPart',
            'data' => [
                'distributor_id' => $this->config['distributor_id'],
                'refund_no' => $refund_no,
                'order_no' => $order_no,
                'out_order_no' => $out_order_no,
                'result' => $result, //通知结果，0失败 1成功 2拒绝
            ]

        ];
        $postUrl = $this->order_refundPart_url . "?apikey=" . $this->config['apikey'] . "&sign=" . $this->getSign($data) . "&data=" . $this->getDecptData($data);
        return $this->weicheng_curl($postUrl, []);
    }
}
