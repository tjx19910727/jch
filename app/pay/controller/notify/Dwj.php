<?php

namespace app\pay\controller\notify;

use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Card\CardModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcUserAddressesModel;

class Dwj
{
    public function scanNotify()
    {
        $postData = input();
        $postData = json2arr($postData);
        actionLog($postData, '大湾鸡扫码用户推送数据');

        $phone = $postData['phone'] ?? '';
        if (!$phone) {
            return returnState(200, 'failed', ['success' => false, 'message' => '手机号不能为空']);
        }

        // $cardLists = CardModel::getList(['bind_id' => $phone]);
        // if ($cardLists) {
        //     $cardLists = $cardLists->toArray();
        //     if (isset($cardLists['data'])) {
        //         $cardLists = $cardLists['data'];
        //     }
        // } else {
        //     $cardLists = [];
        // }

        // $addressLists = WcUserAddressesModel::getList(['bind_id' => $phone]);
        // if ($addressLists) {
        //     $addressLists = $addressLists->toArray();
        //     if (isset($addressLists['data'])) {
        //         $addressLists = $addressLists['data'];
        //     }
        // } else {
        //     $addressLists = [];
        // }

        $response = [
            'success' => true,
            'message' => '登录成功',
            'uid' => $postData['uid'] ?? 0,
            'nickname' => $postData['nickname'] ?? '',
            'avatar' => $postData['avatar'] ?? '',
            'phone' => $phone,
            // 'integral' => $postData['integral'] ?? 0,
            // 'now_money' => $postData['now_money'] ?? 0,
            // 'couponlist' => $postData['couponlist'] ?? [],
            // 'addressList' => $postData['addressList'] ?? [],
            // 'card_lists' => $cardLists,
            // 'address_lists' => $addressLists,
        ];

        $machineId = trim($postData['machine_id'] ?? ($postData['machine_code'] ?? ($postData['machine code'] ?? '')));
        if (!$machineId) {
            actionLog($postData, 'dwj scanNotify缺少设备编号，未下发MQ');
            return returnState(100, 'machine_id_require', [
                'success' => false,
                'message' => '缺少设备编号(machine_id/machine_code/machine code)',
            ]);
        }

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
            ], 'dwj scanNotify结果MQ下发');

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

        return returnState(200, 'success', $response);
    }
}
