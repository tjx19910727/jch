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
    
    /**
     * 接收微程商品上下架状态同步。
     */
    public function syncGoodsInfo()
    {
        // 商品信息：{"product":{"no":"VC2607231004","is_pub":1}}
        $postData = input();
        $postData = json2arr($postData);
        actionLog($postData, '最新商品数据');

        $product = isset($postData['product']) && is_array($postData['product'])
            ? $postData['product']
            : [];
        $goodsNo = trim(strval(isset($product['no']) ? $product['no'] : ''));
        $isPub = isset($product['is_pub']) ? intval($product['is_pub']) : -1;
        if ($goodsNo === '') {
            actionLog($postData, 'syncGoodsInfo商品编码为空，拒绝本次推送');
            return $this->textResponse('product_no_required', 422);
        }
        if (!in_array($isPub, [0, 1], true)) {
            actionLog($postData, 'syncGoodsInfo商品上下架状态错误，拒绝本次推送');
            return $this->textResponse('is_pub_invalid', 422);
        }

        $goods = Db::name('wc_goods')->where('no', $goodsNo)->field('id,no,is_pub')->find();
        if (!$goods) {
            actionLog(['no' => $goodsNo, 'is_pub' => $isPub], 'syncGoodsInfo商品不存在');
            return $this->textResponse('product_not_found', 404);
        }

        Db::startTrans();
        try {
            Db::name('wc_goods')->where('no', $goodsNo)->update(['is_pub' => $isPub]);
            $channelUpdateCount = 0;
            if ($isPub === 1) {
                $channelUpdateCount = Db::name('wc_machine_channel')
                    ->where('out_no', $goodsNo)
                    ->where('is_hidden', 1)
                    ->update(['is_hidden' => 2]);
            } else {
                $channelUpdateCount = Db::name('wc_machine_channel')
                    ->where('out_no', $goodsNo)
                    ->where('is_hidden', 2)
                    ->update(['is_hidden' => 1]);
            }
            Db::commit();
            actionLog([
                'no' => $goodsNo,
                'is_pub' => $isPub,
                'channel_update_count' => $channelUpdateCount,
            ], 'syncGoodsInfo商品状态同步完成');
        } catch (\Throwable $e) {
            Db::rollback();
            actionException($e, 1);
            return $this->textResponse('sync_failed', 500);
        }

        return $this->textResponse('ok');
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
