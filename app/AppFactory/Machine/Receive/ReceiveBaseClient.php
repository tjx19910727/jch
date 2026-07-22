<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/24
 * Time: 10:48
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineDetailsTrait;
use app\AppFactory\Machine\MachineBaseClient;
use app\AppFactory\RabbitMq\MqProducer;
use think\exception\ValidateException;
use think\facade\Filesystem;
use think\facade\Request;

class ReceiveBaseClient extends MachineBaseClient
{
    use MachineOnlineDetailsTrait;
    use AuthManagerTrait;

    public $message = [];
    public $noCheckMac = ["logoutH5",'test'];

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->data = json2arr($this->config['data']);
        $this->machine['last_online_time'] = time();
        $this->machine['online'] = 1;

        $action = Request::action();
        if (!in_array($action,$this->noCheckMac)) {
            $checkMac = $this->checkMac($this->config['mac'] ?? "");
            if ($checkMac !== true) {
                actionLog($this->config, "上报的数据", "mac_check");
                actionLog($checkMac, "Mac验证失败", "mac_check");
                $checkMac->send();
                die();
            }
        }

        $set = $this->setSignKey();
        if ($set !== true) {
            $set->send();
            die();
        }

        if (!isset($this->data['msgType']) || (isset($this->data['msgType']) && $this->data['msgType'] != "heartbeat")) {
            $this->heartbeat();
        }

        $this->newRecord();

        $this->ignoreList = (config("auth_manager_log_list.ignore")['machine'] ?? []);
        $this->apiUrl = request()->action();
        $this->recordManagerLog([],2);
    }

    /**
     * 优惠券/满减后金额为0时，设备端不会再调用支付接口，这里直接完成支付并触发出货。
     */
    protected function completeZeroPayOrderIfNeeded($orderId, $reason = '')
    {
        $order = $this->getSaleOrdersFind(['order_id' => $orderId]);
        if (!$order) {
            return ['handled' => false, 'success' => false, 'msg' => $this->lang("VSaleOrders.order_not_data")];
        }
        $order = is_object($order) && method_exists($order, 'toArray') ? $order->toArray() : (array)$order;
        if ($this->isMallPointsExchangeOrder($order)) {
            return [
                'handled' => false,
                'success' => true,
                'order' => $this->buildOrderPayActionData($order, true, false),
            ];
        }
        if (bccomp(strval($order['total_price'] ?? 0), '0.01', 2) >= 0) {
            return ['handled' => false, 'success' => true, 'order' => $this->buildOrderPayActionData($order)];
        }
        if (intval($order['pay_status'] ?? 0) == 3) {
            return ['handled' => true, 'success' => true, 'order' => $this->buildOrderPayActionData($order, false, true)];
        }

        $this->startTrans();
        try {
            $order['total_price'] = '0.00';
            $order['pay_status'] = 3;
            $order['pay_time'] = time();
            $order['pay_type'] = 0;
            $order['pay_method'] = 1;
            $order['pay_code'] = $reason ? $reason : 'zero_pay';
            $order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']], 0);
            $this->order = $order;
            actionLog(['order_id' => $order['order_id'], 'reason' => $reason], '设备端0元单免支付完成');
            $success = $this->paymentSuccessful();
            $result = $this->checkTrans($success, 0);
            if (!$result) {
                return ['handled' => true, 'success' => false, 'msg' => $this->lang("action_fail")];
            }
            $latest = $this->getSaleOrdersFind(['order_id' => $order['order_id']]);
            $latest = is_object($latest) && method_exists($latest, 'toArray') ? $latest->toArray() : (array)$latest;
            return ['handled' => true, 'success' => true, 'order' => $this->buildOrderPayActionData($latest, false, true)];
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return ['handled' => true, 'success' => false, 'msg' => $e->getMessage()];
        }
    }

    protected function buildOrderPayActionData($order, $payRequired = null, $zeroPay = null)
    {
        $order = is_object($order) && method_exists($order, 'toArray') ? $order->toArray() : (array)$order;
        $details = isset($order['details']) ? $order['details'] : [];
        if (!$details && !empty($order['order_id'])) {
            $details = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']], 0);
        }
        if (is_object($details) && method_exists($details, 'toArray')) {
            $details = $details->toArray();
        }
        if (is_array($details)) {
            foreach ($details as $key => $detail) {
                if (is_object($detail)) {
                    $details[$key] = method_exists($detail, 'toArray') ? $detail->toArray() : (array)$detail;
                }
            }
        }
        $order['details'] = is_array($details) ? array_values($details) : [];
        $needPay = bccomp(strval($order['total_price'] ?? 0), '0.01', 2) >= 0 && intval($order['pay_status'] ?? 0) != 3;
        if ($payRequired !== null) {
            $needPay = (bool)$payRequired;
        }
        $isZeroPay = !$needPay && bccomp(strval($order['total_price'] ?? 0), '0.01', 2) < 0;
        if ($zeroPay !== null) {
            $isZeroPay = (bool)$zeroPay;
        }
        return [
            'order' => $order,
            'pay_required' => $needPay,
            'zero_pay' => $isZeroPay,
            'next_action' => $needPay ? 'pay' : 'wait_out_goods',
        ];
    }

    /**
     * 通过Mac地址生成SignKey，并下发给设备，只有mac参数才触发
     */
    public function setSignKey()
    {
        if (isset($this->data['mac']) && !isset($this->data['sign'])) {
            try {
                actionLog($this->data, "上报的数据","setSignKey");
                actionLog(['mac_address' => $this->machine['mac_address'], "mac" => $this->data['mac']], "系统-终端Mac地址","setSignKey");
                $signKey = $this->machine['signKey'];
                // SignKey为空或SignKeyTime超过3600秒，生成新的Key并下发
                if (!$signKey) {
                    $signKey = md5($this->data['mac'] . time() . env("api.md5Key"));
                    $this->updateMachine(['m_id' => $this->machine['m_id'], 'signKey' => $signKey, 'signKeyTime' => time()]);
                }
                actionLog(['machine' => $this->machine], "设备","setSignKey");
                actionLog(['signKey' => $signKey], "SignKey","setSignKey");

                if ($signKey) {
                    $now = time();
                    $expiresIn = intval(config('rabbit_mq.machine_sign_key_expires_in') ?: 3600);
                    if ($expiresIn < 300) $expiresIn = 3600;
                    $timestampTolerance = intval(config('rabbit_mq.machine_receive_timestamp_tolerance') ?: 180);
                    if ($timestampTolerance < 120) $timestampTolerance = 120;
                    $data = [
                        "msg_id" => uniqid(),
                        "timestamp" => $now,
                        "server_time" => $now,
                        "machine_id" => $this->machine['machine_id'],
                        "signKey" => $signKey,
                        "expires_in" => $expiresIn,
                        "expires_at" => $now + $expiresIn,
                        "timestamp_tolerance" => $timestampTolerance,
                    ];
                    actionLog($data, '发送至MQ服务器的数据',"setSignKey");
                    $this->dataRecord(2, 2);

                    actionLog($this->mqQueue,'下发队列名',"setSignKey");
                    $result = MqProducer::dataSend($data, $this->mqQueue);
                    actionLog($result, '发送结果',"setSignKey");
                    @cache($this->machine['machine_id'] . ".signKey", $signKey, 3600 * 5);
                    actionLog(@cache($this->machine['machine_id'] . ".signKey"), $this->machine['machine_id'] . '生成SignKey',"setSignKey");
                    return $this->r(200,'处理成功');
                }
            } catch (\Exception $e) {
                actionException($e,1);
                return $this->rTryCatch($e->getMessage());
            }
        }
        return true;
    }


    /**
     * 设备上传媒体文件
     * @param string $folder
     * @return array|string
     */
    public function uploadFiles()
    {
        $folder = $this->data['folder'];
        $file = request()->file("file");
        if (!$file) return returnState(100,'上传失败，file不能为空');
        if ($folder) {
            $folderPath = root_path("public/uploads/" . $folder);
            if (!is_dir($folderPath)) {
                @mkdir($folderPath);
                @chmod($folderPath,0777);
            }
        }
        try {
            validate(
                [
                    'file' => [
//                        "fileSize" => 2 * 1024 * 1024,
                        "fileExt" => "jpg,jpeg,gif,png,mp4,flv,wav,aiff,aac,flac,ogg,m4a,amr,wma,pcm,log",
                    ],
                ],
                [
//                    "file.fileSize" => "fileSize",
                    "file.fileExt" => "fileExt",
                ]
            )->check(['file' => $file]);
            $diskName = env("fileSystem.diskName");     // 上传本地
//            $diskName = "aliyun";    // 上传OSS服务器
            $saveName = Filesystem::disk($diskName)->putFile($folder, $file);
            $path = Filesystem::getDiskConfig($diskName, 'url') . str_replace('\\', '/', $saveName);
            return returnState(200,$this->lang("uploadSuccess"),$path);
        } catch (ValidateException $e) {
            return returnValidate($this->lang($e->getMessage()));
        }
    }
}
