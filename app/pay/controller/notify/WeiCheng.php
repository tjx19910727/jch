<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/1/29
 * Time: 10:07
 */

namespace app\pay\controller\notify;

use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Card\CardModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcUserAddressesModel;

class WeiCheng
{

    public function scanNotify(){
        //用户信息入库等。{"phone":"13714759235","integral":2088,"couponlist": [],"addressList":[],"machine code":"JcHM-H2D-0064"}
        $postData = input();
        $postData = json2arr($postData);
        actionLog($postData, '微程退款推送数据');
        $phone = $postData['phone'] ?? '';
        if (!$phone) {
            return returnState(200, 'failed', ['success' => false, 'message' => '手机号不能为空']);
        }

        $cardLists = CardModel::getList(['bind_id' => $phone]);
        if ($cardLists) {
            $cardLists = $cardLists->toArray();
            if (isset($cardLists['data'])) {
                $cardLists = $cardLists['data'];
            }
        } else {
            $cardLists = [];
        }

        $addressLists = WcUserAddressesModel::getList(['bind_id' => $phone]);
        if ($addressLists) {
            $addressLists = $addressLists->toArray();
            if (isset($addressLists['data'])) {
                $addressLists = $addressLists['data'];
            }
        } else {
            $addressLists = [];
        }

        $response = [
            'success' => true,
            'message' => '登录成功',
            'phone' => $phone,
            'integral' => $postData['integral'] ?? 0,
            'couponlist' => $postData['couponlist'] ?? [],
            'addressList' => $postData['addressList'] ?? [],
            'card_lists' => $cardLists,
            'address_lists' => $addressLists,
        ];

        // 完成后的数据通过MQ下发给设备（兼容微程字段 machine_id / machine_code / machine code）
        $machineId = trim($postData['machine_id'] ?? ($postData['machine_code'] ?? ($postData['machine_code'] ?? '')));
        if ($machineId) {
            try {
                $mqPayload = [
                    'data' => $response,
                ];
                $mqResult = AppFactory::machine(['machine_id' => $machineId])->sendMq->sendMq('scanNotify', $mqPayload);
                $mqResultArr = obj2arr($mqResult);
                actionLog([
                    'machine_id' => $machineId,
                    'msgType' => 'scanNotify',
                    'mq_payload' => $mqPayload,
                    'mq_result' => $mqResultArr,
                ], 'scanNotify结果MQ下发');

                // 下发失败时显式返回，避免接口看起来成功但设备实际未收到
                if (!is_array($mqResultArr) || !isset($mqResultArr['state']) || intval($mqResultArr['state']) !== 200) {
                    return returnState(100, 'mq_send_fail', [
                        'success' => false,
                        'machine_id' => $machineId,
                        'mq_result' => $mqResultArr,
                    ]);
                }
            } catch (\Exception $e) {
                actionException($e, 1);
                return returnState(100, 'mq_send_exception', [
                    'success' => false,
                    'machine_id' => $machineId,
                    'message' => $e->getMessage(),
                ]);
            }
        } else {
            actionLog($postData, 'scanNotify缺少设备编号，未下发MQ');
            return returnState(100, 'machine_id_require', [
                'success' => false,
                'message' => '缺少设备编号(machine_id/machine_code/machine code)',
            ]);
        }

        // return returnState(200, 'success', $response);
    }
    
    //最新商品信息同步
    public function syncGoodsInfo(){
		//用户信息入库等。
        $postData = input();
        $postData = json2arr($postData);
        actionLog($postData, '最新商品数据');
        return 'ok';                                                                                                                                                                                                                                    
    }

    public function refund()
    {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               
        try {
            $postData = input();
            $postData = json2arr($postData);
            actionLog($postData, '微程退款推送数据');
            //调用后台退款接口
        } catch (\Exception $e) {
            actionException($e,1);
            echo  "ok";
            die();
        }
    }

    public function refundAll()
    {
        try {
            $postData = input();
            $postData = json2arr($postData);
            actionLog($postData, '微程退款推送数据');
            //调用后台退款接口
        } catch (\Exception $e) {
            actionException($e,1);
            echo  "ok";
            die();
        }
    }
}