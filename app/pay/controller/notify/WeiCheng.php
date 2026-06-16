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
use app\AppFactory\Kernel\Model\WeiCheng\WcUserLoginInfoModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcUserAddressesModel;
use think\facade\Db;

class WeiCheng
{

    public function scanNotify(){
        //用户信息入库等。{"phone":"13714759235","integral":2088,"couponlist": [],"addressList":[],"machine code":"JcHM-H2D-0064"}
        $postData = input();
        $postData = json2arr($postData);
        actionLog($postData, '微程登录推送数据');
        $phone = trim((string)($postData['phone'] ?? ''));
        if (!$phone) {
            actionLog($postData, 'scanNotify手机号为空，拒绝本次推送');
            return $this->textResponse('phone_required', 422);
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
        $machineId = trim($postData['machine_id'] ?? ($postData['machine_code'] ?? ($postData['machine code'] ?? '')));
        if (!$machineId) {
            actionLog($postData, 'scanNotify缺少设备编号，拒绝本次推送');
            return $this->textResponse('machine_id_required', 422);
        }

        $machine = Db::name('machine')->where('machine_id', $machineId)->field('m_id,ao_id')->find();
        if (!$machine) {
            actionLog($postData, 'scanNotify设备不存在，拒绝本次推送');
            return $this->textResponse('machine_not_found', 422);
        }

        $loginInfo = WcUserLoginInfoModel::create([
            'm_id' => intval($machine['m_id']),
            'machine_id' => $machineId,
            'ao_id' => intval($machine['ao_id']),
            'phone' => $phone,
            'login_data' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'mq_status' => 0,
            'mq_result' => '',
        ]);
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

            if (!is_array($mqResultArr) || !isset($mqResultArr['state']) || intval($mqResultArr['state']) !== 200) {
                $this->updateLoginInfoMqStatus($loginInfo->wuli_id, 2, $mqResultArr);
                actionLog($mqResultArr, 'scanNotify MQ下发失败，等待设备HTTP主动获取');
            }
            if (is_array($mqResultArr) && isset($mqResultArr['state']) && intval($mqResultArr['state']) === 200) {
                $this->updateLoginInfoMqStatus($loginInfo->wuli_id, 1, $mqResultArr);
            }
        } catch (\Throwable $e) {
            $this->updateLoginInfoMqStatus($loginInfo->wuli_id, 2, $e->getMessage());
            actionException($e, 1);
        }

        return $this->textResponse('ok');
    }

    protected function updateLoginInfoMqStatus($loginInfoId, $mqStatus, $mqResult)
    {
        try {
            if (!is_string($mqResult)) {
                $mqResult = json_encode($mqResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            WcUserLoginInfoModel::update([
                'mq_status' => intval($mqStatus),
                'mq_result' => $mqResult,
            ], ['wuli_id' => intval($loginInfoId)]);
        } catch (\Throwable $e) {
            actionException($e, 1);
        }
    }

    protected function textResponse($content, $statusCode = 200)
    {
        return response($content, $statusCode, ['Content-Type' => 'text/plain; charset=utf-8']);
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
