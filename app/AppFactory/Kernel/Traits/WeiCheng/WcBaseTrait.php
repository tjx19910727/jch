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
use app\AppFactory\Kernel\Model\WeiCheng\WcUserAddressesModel;

trait WcBaseTrait
{
    use WcGoodsTrait,  WcRequestLogsTrait, SaleOrdersTrait;

    public function initWcBase()
    {
        if (env("CglPay.is_test")) {
            $this->config = [
                "distributor_id" => "520253",
                "apikey" => "ab50e9d1038e4905b1d5f1f263e69e18_n",
                "apisecret" => "d1e79b35bc6f491993f873c56b163f47",
                "secretkey" => "8f8d4818c49f44e6bb53d04b",
                "domain" => "https://test-admin-weicheng.jchtechnologies.com",
                "apiDomain" => "https://test-api-weicheng.jchtechnologies.com",
            ];
        } else {
            $this->config = [
                "distributor_id" => "520443",
                "apikey" => "5e819581b8a04b2b98f767c517c100fb_n",
                "apisecret" => "2674529f13c84ea0a8d4d00461c1243c",
                "secretkey" => "a5e0267f83d04741a9a72fdc",
                "domain" => "https://admin-weicheng.jchtechnologies.com",
                "apiDomain" => "https://api-weicheng.jchtechnologies.com",
            ];
        }

        $this->goods_type_sync_url = $this->config['domain'] . "/api/goods/typeSync";
        $this->goods_sync_url = $this->config['domain'] . "/api/goods/sync";
        $this->order_add_url = $this->config['domain'] . "/api/order/add";
        $this->order_refund_url = $this->config['domain'] . "/api/order/refund";
        $this->order_detail_url = $this->config['domain'] . "/api/order/detail";
        $this->order_refundPart_url = $this->config['domain'] . "/api/order/refundPart";
        $this->get_sms_code_url = $this->config['apiDomain'] . "/msvc-shop/v1/mp/user/phone/send/code";
        $this->phone_login_url = $this->config['apiDomain'] . "/msvc-shop/v1/mp/user/phoneLogin";
        $this->user_sync_points = $this->config['apiDomain'] . "/msvc-shop/v1/mp/user/syncIntegral";
        $this->get_points_qrcode = $this->config['apiDomain'] . "/msvc-shop/v1/mp/user/getIntegralQrcode";
        $this->query_hotel_info_url = $this->config['apiDomain'] . "/msvc-shop/v1/mp/user/hotel/queryDays";
        $this->query_user_info_url = $this->config['apiDomain'] . "/msvc-shop/v1/mp/user/queryUserRights";
        $this->query_login_qrcode_url = $this->config['apiDomain'] . "/msvc-shop/v1/mp/user/getLoginQrcode";
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


    public function weicheng_curl($url, $postFields = [], $header = [], $logOptions = [])
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
        $curlError = '';
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            echo 'Curl error: ' . $curlError;
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($this->shouldWriteWcRequestLog($status, $curlError, $response, $logOptions)) {
            $requestHeaders = $header ? $header : ['Content-Type: application/x-www-form-urlencoded'];
            $requestBody = isset($logOptions['request_body']) ? $logOptions['request_body'] : $postFields;
            $this->writeWcRequestLogSafely([
                'request_url' => $this->sanitizeWcRequestUrl($url),
                'request_headers' => $this->encodeWcRequestLogData($requestHeaders),
                'request_body' => $this->encodeWcRequestLogData($requestBody),
                'response_body' => $response,
                'response_status' => $status,
                'type' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        curl_close($ch);
        return ['response' => $response, 'status' => $status];
    }

    /**
     * 请求日志属于辅助能力，写入失败时降级到PHP错误日志，不中断业务请求。
     */
    protected function writeWcRequestLogSafely($logData)
    {
        try {
            $this->addWcRequestLogs($logData);
            return true;
        } catch (\Throwable $e) {
            $message = str_replace(["\r", "\n"], ' ', $e->getMessage());
            error_log('[wc_request_logs] write failed: ' . substr($message, 0, 1000));
            return false;
        }
    }

    /**
     * 批量同步可仅记录失败请求，其他调用默认保持全量记录。
     */
    protected function shouldWriteWcRequestLog($status, $curlError, $response, $logOptions)
    {
        $mode = isset($logOptions['mode']) ? $logOptions['mode'] : 'all';
        if ($mode !== 'failure') return true;
        if ($curlError !== '' || intval($status) !== 200) return true;

        $expectedKey = isset($logOptions['expected_response_key']) ? $logOptions['expected_response_key'] : '';
        if ($expectedKey === '') return false;
        $responseData = json_decode($response, true);
        return !is_array($responseData) || !isset($responseData[$expectedKey]);
    }

    /**
     * 日志保留业务参数，但对认证信息和个人敏感字段脱敏。
     */
    protected function sanitizeWcRequestLogData($data)
    {
        if (!is_array($data)) return $data;
        $sensitiveKeys = ['apikey', 'sign', 'token', 'code', 'phone', 'mobile', 'link_phone', 'identity_card'];
        foreach ($data as $key => $value) {
            $normalizedKey = strtolower(trim((string)$key));
            if (is_int($key) && is_string($value) && stripos($value, 'token:') === 0) {
                $data[$key] = 'token: ***';
            } elseif (in_array($normalizedKey, $sensitiveKeys, true)) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeWcRequestLogData($value);
            }
        }
        return $data;
    }

    /**
     * 非法UTF-8等编码异常时保留可编码字段，避免request_body再次变为空值。
     */
    protected function encodeWcRequestLogData($data)
    {
        $data = $this->sanitizeWcRequestLogData($data);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json !== false) return $json;

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        return $json !== false ? $json : '{}';
    }

    /**
     * URL只保留接口定位信息，密钥、签名和加密载荷写入前统一脱敏。
     */
    protected function sanitizeWcRequestUrl($url)
    {
        return preg_replace('/([?&])(apikey|sign|data|token|code|phone)=([^&]*)/i', '$1$2=***', $url);
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
        return $this->weicheng_curl($postUrl, [], [], [
            'mode' => 'failure',
            'expected_response_key' => 'data',
            'request_body' => $data,
        ]);
    }

    public function synchronizeGoodsLists2Db($goods_lists, $type, $syncBatchNo = '')
    {
        foreach ($goods_lists as $goods) {
            if (empty($goods['no'])) {
                continue;
            }
            $wc_goods = $this->getWcGoodsFind(['no' => $goods['no']]);
            $goods['type'] = $type;
            if ($syncBatchNo !== '') {
                $goods['sync_status'] = $syncBatchNo . '_1';
            }
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
        return $this->weicheng_curl($postUrl, [], [], [
            'mode' => 'failure',
            'expected_response_key' => 'product',
            'request_body' => $data,
        ]);
    }

    public function synchronizeGoods2Db($updateData, $syncBatchNo = '')
    {
        if ($syncBatchNo !== '') {
            $updateData['sync_status'] = $syncBatchNo . '_1';
        }
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
        $resourcesArray = json_decode($wc_goods['resourcesArray'], true) ?? [];
        $pic = '';
        if($wc_goods['type']  == 1) {//抢购商品没有子商品信息，这里构造wc_goods_local数据
            $wc_goods_local = $this->getWcGoodsLocalFind(['no' => $wc_goods['no'], 'out_no' => $wc_goods['no']]);
            $pic = isset($resourcesArray[0]['url']) ? $resourceDomain . $resourcesArray[0]['url'] : '';
            $setData = [
                'g_id' => 9999,
                'out_no' => $wc_goods['no'],
                'no' => $wc_goods['no'],
                'type' => $type,
                'g_name' => $wc_goods['name'] ?? '',
                'g_type' => $wc_goods['type'] ?? 0,
                'g_type_name' => $wc_goods_type_arr[$wc_goods['type']] ?? '',   
                'retail_price' => $wc_goods['price'] ?? '0',
                'pic' => $pic,
                'sell_channel' => 3,
                'desc' => '',
                'status' => 1,
                'channel_code' => 'Z10',
                'daysInfo' => isset($good['daysInfo']) && !empty($good['daysInfo']) ? json_encode($good['daysInfo']) : '',
                'isNeedReserve' => $wc_goods['isNeedReserve'] ?? '0',
                'gift_points' => $wc_goods['gift_points'] ?? 0,
            ];

            if (!$wc_goods_local) {
                return $this->addWcGoodsLocal($setData);
            } else {
                return $this->updateWcGoodsLocal($setData, ['no' => $wc_goods['no'], 'out_no' => $wc_goods['no']]);
            }
        }
        if (!is_null($wc_goods['goods'])) {//子商品信息
            //子商品信息
            $goods = json_decode($wc_goods['goods'], true);
            foreach ($goods as $k => $good) {
                $wc_goods_local = $this->getWcGoodsLocalFind(['no' => $good['no'], 'out_no' => $no]);
                $pic = isset($resourcesArray[$k]['url']) ? $resourceDomain . $resourcesArray[$k]['url'] : '';
                $setData = [
                    'g_id' => $good['g_id'] ?? 9999,
                    'out_no' => $no ?? '',
                    'no' => $good['no'] ?? '',
                    'type' => $type,
                    'g_name' => $good['name'] ?? '',
                    'g_type' => $good['type'] ?? 0,
                    'g_type_name' => $wc_goods_type_arr[$good['type']] ?? '',
                    'retail_price' => $good['price'] ?? '',
                    'pic' => $pic,
                    'sell_channel' => 3,
                    'desc' => $good['notice'] ?? '',
                    'status' => 1,
                    'gift_points' => $good['present_integral'] ?? 0,
                    'channel_code' => 'Z10',
                    'daysInfo' => isset($good['daysInfo']) && !empty($good['daysInfo']) ? json_encode($good['daysInfo']) : '',
                ];
                if (!$wc_goods_local) {
                    $this->addWcGoodsLocal($setData);
                } else {
                    $this->updateWcGoodsLocal($setData, ['no' => $good['no'], 'out_no' => $no]);
                }
            }
        }
        if (!is_null($wc_goods['combination_goods'])) {//组合商品信息
            // if($wc_goods['daysInfo'] && !is_null($wc_goods['daysInfo'])) {
            //     $daysInfo = json_decode($wc_goods['daysInfo'], true);
            // }
            $combination_goods = json_decode($wc_goods['combination_goods'], true) ?? [];
            foreach ($combination_goods as $kk => $combind_good) {
                $pic = isset($resourcesArray[$kk]['url']) ? $resourceDomain . $resourcesArray[$kk]['url'] : '';
                $combindSetData = [
                    'g_id' => $combind_good['g_id'] ?? '9999',
                    'out_no' => $no ?? '',
                    'no' => $combind_good['no'] ?? '',
                    'type' => $type,
                    'g_name' => $combind_good['name'] ?? '',
                    'g_type' => $combind_good['type'] ?? 0,
                    'g_type_name' => $wc_goods_type_arr[$combind_good['type']] ?? '',
                    'retail_price' => $combind_good['price'] ?? '',
                    'pic' => $combind_good['main_img'] ?? '',
                    'sell_channel' => 3,
                    'desc' => $combind_good['notice'] ?? '',
                    'status' => 1,
                    'gift_points' => $combind_good['present_integral'] ?? 0,
                    'channel_code' => 'Z10',
                    'isNeedReserve' => $combind_good['isNeedReserve'] ?? '0',
                ];
                //单独处理一下daysInfo
                $combindSetData['daysInfo'] = '';
                if(($wc_goods['type'] == 3 ||$wc_goods['type'] == 11) && $combindSetData['g_id'] == 9999){
                    $combindSetData['daysInfo'] = $wc_goods['daysInfo'];
                    //对daysInfo处理一下，stock=surplus_stock
                    $daysInfo = json_decode($combindSetData['daysInfo'], true);
                    if (isset($daysInfo['stock'])) {
                        $daysInfo['surplus_stock'] = $daysInfo['stock'];
                        unset($daysInfo['stock']);
                        $combindSetData['daysInfo'] = json_encode($daysInfo);
                    }
                }
                $wc_goods_local = $this->getWcGoodsLocalFind(['no' => $combindSetData['no'], 'out_no' => $no]);
                if (!$wc_goods_local) {
                    $this->addWcGoodsLocal($combindSetData);
                } else {
                    $this->updateWcGoodsLocal($combindSetData, ['no' => $combindSetData['no'], 'out_no' => $no]);
                }

            }
        }
        return true;
    }

    public function getSmsCode($phone, $machine_id)
    {
        $this->initWcBase();
        $postUrl = $this->get_sms_code_url . "?phone=" . $phone . "&machine_code=" . $machine_id;
        return $this->weicheng_curl($postUrl, [], [], ['request_body' => [
            'phone' => $phone,
            'machine_code' => $machine_id,
        ]]);
    }


    public function wcLoginUser($phone, $machine_id, $code)
    {
        $this->initWcBase();
        $postUrl = $this->phone_login_url . "?phone=" . $phone . "&machine_code=" . $machine_id . "&code=" . $code;
        return $this->weicheng_curl($postUrl, [], [], ['request_body' => [
            'phone' => $phone,
            'machine_code' => $machine_id,
            'code' => $code,
        ]]);
    }

    public function wcUserSyncPoints($token, $integral, $op_type)
    {
        $this->initWcBase();
        $postUrl = $this->user_sync_points . "?op_type=" . $op_type . "&integral=" . (int)$integral;
        $header = array('token: ' . $token);
        return $this->weicheng_curl($postUrl, [], $header, ['request_body' => [
            'op_type' => $op_type,
            'integral' => (int)$integral,
        ]]);
    }


    public function wcPointsQrCode($integral)
    {
        $this->initWcBase();
        $postUrl = $this->get_points_qrcode . "?integral=" . (int)$integral;
        return $this->weicheng_curl($postUrl, [], [], ['request_body' => ['integral' => (int)$integral]]);
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
        $wc_order_no = json_decode($detail['wc_order_no'] ?? '{}', true) ?: [];

        $buy_date_range = [];
        foreach($wc_order_no as $no => $value) {
            if(!empty($value['order_date'])) {
                if(count($value['order_date']) == 1){
                    $buy_date_range = [
                        'start' => $value['order_date'],
                        'end' => $value['order_date'],
                    ];
                }else{
                    array_multisort($value['order_date'], SORT_ASC);
                    $buy_date_range = [
                        'start' => current($value['order_date']),
                        'end' => end($value['order_date']),
                    ];
                }
                
            }

            $realChannelCode = isset($value['real_channel_code']) ? trim((string)$value['real_channel_code']) : '';
            $data = [
                'out_order_no' => $order['trade_no'] . '#' . $detail['sod_id'],
                'goods_no' => $no,
                'goods_quantity' => $detail['quantity'],
                'link_man' => '会员',
                'link_phone' => $order['mobile'] ?: '',
                'identity_card' => '',
                'link_address' => '',
                'link_remark' => '',
                'trip_date' => date('Y-m-d'),
                'distributor_id' => $this->config['distributor_id'],
                'machine_id' => $order['machine_id'] ?? '',
                'dispensing_status' => $realChannelCode == 'Z10' ? 2 : 1,
            ];
            if(!empty($buy_date_range)) $data['buy_date_range'] = json_encode($buy_date_range);
            actionLog($data, "子订单同步数据");
            $postUrl = $this->order_add_url . "?apikey=" . $this->config['apikey'] . "&sign=" . $this->getSign($data) . "&data=" . $this->getDecptData($data);
            $res = $this->weicheng_curl($postUrl, [], [], ['request_body' => $data]);
            // $res['response'] = '{"order_no":"O757423599403734","orderNo":"O757423599403734","tickets":["68234301"],"tickets_new":[{"ticket":"68234301","num":1,"qr_code":"https://oss-weicheng.jchtechnologies.com/upload/2026/03/04/8c0ae373080843e4a6f358361e20bd21.jpg","qr_code_url":"https://oss-weicheng.jchtechnologies.com/upload/2026/03/04/8c0ae373080843e4a6f358361e20bd21.jpg"}],"ticket_check_style":0,"tip":"出库成功","status":"success"}';
            $res_arr = json_decode($res['response'] ?? '', true);
            if (!is_array($res_arr)) {
                actionLog(['detail' => $detail, 'response' => $res['response'] ?? ''], "子订单同步失败：微程返回格式异常");
                $wc_order_no[$no]['order_no'] = '';
            } elseif (($res_arr['status'] ?? '') == "fail") {
                actionLog($detail, "子订单同步失败" . ($res_arr['tip'] ?? ''));
                $wc_order_no[$no]['order_no'] = '';
            } else {
                $wc_order_no[$no]['order_no'] = $res_arr['order_no'] ?? '';
                $wc_order_no[$no]['response'] = $res_arr;
            }
        }
        return $wc_order_no;
    }

    public function orderRefundSync2Wc($order, $detail)
    {
        $this->initWcBase();
        $wc_order_no = json_decode($detail['wc_order_no'] ?? '{}', true) ?: [];
        foreach($wc_order_no as $no => $value) {
            if(!empty($value['order_no'])) {
                $data = [
                    'distributor_id' => $this->config['distributor_id'],
                    'order_no' => $value['order_no'],
                    'out_order_no' => $order['trade_no'] . '#' . $detail['sod_id'],
                ];
                $postUrl = $this->order_refund_url . "?apikey=" . $this->config['apikey'] . "&sign=" . $this->getSign($data) . "&data=" . $this->getDecptData($data);
                $flag[] =  $this->weicheng_curl($postUrl, [], [], ['request_body' => $data]);
                actionLog($flag, "退款接口返回数据");
            }
        }
        return true;
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
        return $this->weicheng_curl($postUrl, [], [], ['request_body' => $data]);
    }

    public function syncWcUserInfo($token)
    {
        $this->initWcBase();
        $header = array('token: ' . $token);
        return $this->weicheng_curl($this->query_user_info_url, [], $header, [
            'request_body' => ['operation' => 'query_user_info'],
        ]);
    }

    public function wcLoginQrCode($machine_id)
    {
        $this->initWcBase();
        $postUrl = $this->query_login_qrcode_url . "?machine_code=" . $machine_id;
        return $this->weicheng_curl($postUrl, [], [], ['request_body' => ['machine_code' => $machine_id]]);
    }
}
