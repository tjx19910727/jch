<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/8/4
 * Time: 19:51
 */

namespace app\AppFactory\Kernel\Traits\Send;

use app\AppFactory\AppFactory;
use app\AppFactory\Notice\Application;

// 公众号模板消息通知入口
trait WxNoticeTrait
{
    /**
     * @var Application
     */
    protected $nApp;

    /**
     * 初始化模板消息通知APP
     */
    protected function initNoticeApp()
    {
        $this->nApp = AppFactory::notice();
    }

    /**
     * 1 发送开门通知
     * @param $store
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendOpenDoorNotice($store)
    {
        $data = [
            "store_id" => $store['store_id'],
            "noticeType" => 1,
            "body" => [
                "store_name" => $store['store_name'],
                "address" => $store['address'],
                "now" => date("Y-m-d H:i:s"),
            ],
        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送通知结果');
    }

    /**
     * 2 售卖模板消息通知
     * @param $store
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendSalesNotice($store)
    {
        $data = [
            "store_id" => $store['store_id'],
            "noticeType" => 2,
            "body" => [
                "total_price" => $store['total_price'],
                "store_name" => $store['store_name'],
                "now" => date("Y-m-d H:i:s"),
//                "address" => $store['address'],
            ],
        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送售卖通知结果');
    }

    /**
     * 3 缺货通知
     * noticeType 3   store_id    terminal_no shelves_number   stock now
     * @param $shelves
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendStockNotice($shelves)
    {
        $data = [
            "store_id" => $shelves['store_id'],
            "noticeType" =>  3,
            "body" => [
                "terminal_no" => '',
                "shelves_number" => '',
                "stock" => $shelves['stock'],
                "now" => date("Y-m-d H:i:s"),
            ],

        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送缺货通知结果');
    }

    /**
     * 发送在离线通知 4 在线通知 5 离线通知
     * @param $store
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendOnOfflineNotice($store)
    {
        $data = [
            "store_id" => $store['store_id'],
            "noticeType" => $store['online'] == 1 ? 4 : 5,
            "body" => [
                "terminal_no" => $store['terminal_no'],
                "address" => $store['address'],
                "now" => date("Y-m-d H:i:s"),
                "mobile" => $store['mobile'],
            ],
        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送在离线通知结果');
    }

    /**
     * 6 未关门通知
     * @param $store
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendUnCloseDoorNotice($store)
    {
        $data = [
            "store_id" => $store['store_id'],
            "noticeType" => 6,
            "body" => [
                "store_name" => $store['store_name'],
                "address" => $store['address'],
                "now" => date("Y-m-d H:i:s"),
                "mobile" => $store['mobile'],
            ],
        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送未关门通知结果');
    }

    /**
     * 7  异常购买通知
     * @param $store
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendAbnormalPurchaseNotice($store)
    {
        $data = [
            "store_id" => $store['store_id'],
            "noticeType" => 7,
            "body" => [
                "now" => date('Y-m-d H:i:s'),
                "address" => $store['address'],
                "store_name" => $store['store_name'],
            ],
        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送异常购买通知结果');
    }

    /**
     * 摄像头上线离线通知，8  摄像头上线通知  9  摄像头离线通知
     * @param $hd
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendCameraOnOfflineNotice($store)
    {
        $data = [
            "store_id" => $store['store_id'],
            "noticeType" => 8,
            "body" => [
                "deviceSerial" => $store['hardware_number'],
//                "address" => $store['address'],
                "now" => date("Y-m-d H:i:s"),
//                "mobile" => $store['mobile'],
            ],
        ];
        if ($store['online'] == 1) {
            $data['noticeType'] = 8;
        }
        if ($store['online'] == 2) {
            $data['noticeType'] = 9;
        }
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送通知结果');
    }

    /**
     * 10 断电通知
     * @param $store
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendOutageNotice($store)
    {
        $data = [
            "store_id" => $store['store_id'],
            "noticeType" => 10,
            "body" => [
                "terminal_no" => $store['terminal_no'],
                "address" => $store['address'],
                "now" => date("Y-m-d H:i:s"),
                "mobile" => $store['mobile'],
            ],
        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送通知结果');
    }

    /**
     * 11 黑名单用户扫码通知
     * @param $user
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendBlackListScanNotice($user)
    {
        $data = [
            "store_id" => $user['store_id'],
            "openid" => $user['openid'],
            "noticeType" => 11,
            "body" => [
                "user_name" => $user['name'],
                "address" => $user['address'],
                "now" => date("Y-m-d H:i:s"),
                "mobile" => $user['mobile'],
            ],
        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送通知结果');
    }

    /**
     * 12 开门请求通知
     * @param $store
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendOpenRequestNotice($store)
    {
        $data = [
            "store_id" => $store['store_id'],
            "noticeType" => 12,
            "body" => [
                "store_name" => $store['store_name'],
                "address" => $store['address'],
                "now" => date("Y-m-d H:i:s"),
                "mobile" => $store['mobile'],
            ],
        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送通知结果');
    }

    /**
     * 13 发送补扣支付订单通知
     * @param $order
     * @return array|bool|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendSupplementaryPaymentNotice($order)
    {
        $data = [
            "store_id" => $order['store_id'],
            "noticeType" => 13,
            "openid" => $order['openid'],
            "url" => $this->getUrl("/mobile/mini.entrance/index"),
            "urlQuery" => [
                "order_id" => $order['order_id'],
                "store_id" => $order['store_id'],
            ],
            "body" => [
                "store_name" => $order['store_name'],
                "user_name" => $order['user_name'],
                "now" => date("Y-m-d H:i:s"),
            ],
        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送通知结果');
        return $sendResult;
    }

    /**
     * 14 发送购买成功通知
     * @param $order
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendPurchaseSuccessfulNotice($order)
    {
        $data = [
            "store_id" => $order['store_id'],
            "noticeType" => 14,
            "openid" => $order['openid'],
            "body" => [
                "store_name" => $order['store_name'],
                "now" => date("Y-m-d H:i:s"),
                "total_price" => $order['total_price'],
                "user_name" => $order['user_name'],
            ],
        ];
        $this->initNoticeApp();
        $sendResult = @$this->nApp->wxTemplate->send($data);
        actionLog($sendResult,'发送通知结果');
    }
}