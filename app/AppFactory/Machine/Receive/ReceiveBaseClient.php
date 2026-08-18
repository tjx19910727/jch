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
use think\facade\Cache;
use think\facade\Filesystem;
use think\facade\Request;

class ReceiveBaseClient extends MachineBaseClient
{
    use MachineOnlineDetailsTrait;
    use AuthManagerTrait;

    public $message = [];
    public $noCheckMac = ["logoutH5",'test'];
    protected $signKeyBootstrapHandled = false;
    protected $signKeyBootstrapFailed = false;
    // 同一设备 signKey 最小重发间隔，单位：秒。
    // 默认20秒，可通过 config/rabbit_mq.php 的 sign_key_resend_cooldown 覆盖。
    // 调小可加速设备重新认证恢复，避免用户付款后长时间等待出货；但不能低于10秒。
    protected $signKeyResendCooldown = 20;
    // 冷却期内设备仍未认证时，最多强制重发 signKey 的次数（避免死循环）。
    protected $signKeyForceResendThreshold = 5;

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
                actionLog([
                    'machine_id' => $this->config['machine_id'] ?? '',
                    'transport' => $this->config['transport'] ?? 'http',
                ], "上报的数据", "mac_check");
                actionLog($checkMac, "Mac验证失败", "mac_check");
                if (($this->config['transport'] ?? '') === 'mq') {
                    throw new \InvalidArgumentException('MQ设备Mac验证失败');
                }
                $checkMac->send();
                die();
            }
        }

        $set = $this->setSignKey();
        if ($set !== true) {
            if (($this->config['transport'] ?? '') === 'mq') {
                if ($this->signKeyBootstrapFailed) {
                    throw new \RuntimeException('MQ signKey下发失败');
                }
                $this->signKeyBootstrapHandled = true;
                return;
            }
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
                'success' => false,
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
                actionLog(['mac_address' => $this->machine['mac_address'], "mac" => $this->data['mac']], "系统-终端Mac地址","setSignKey");
                $signKey = $this->machine['signKey'];
                // SignKey 只在设备尚未分配时生成，重试认证复用已有 Key。
                if (!$signKey) {
                    $signKey = md5($this->data['mac'] . time() . env("api.md5Key"));
                    $this->updateMachine(['m_id' => $this->machine['m_id'], 'signKey' => $signKey, 'signKeyTime' => time()]);
                } else {
                    // 复用已有Key时也刷新过期时间，避免旧Key在长时间未认证后被判超时
                    $this->updateMachine(['m_id' => $this->machine['m_id'], 'signKeyTime' => time()]);
                }

                if ($signKey) {
                    $cooldown = intval($this->signKeyResendCooldown);
                    // 支持通过 config/rabbit_mq.php 覆盖冷却期，便于按需调整认证恢复速度
                    $configCooldown = intval(config('rabbit_mq.sign_key_resend_cooldown') ?: 0);
                    if ($configCooldown > 0) $cooldown = $configCooldown;
                    // 下限10秒，防止设备高频重试导致队列/DB压力过大
                    if ($cooldown < 10) $cooldown = 10;
                    $cooldownKey = $this->machine['machine_id'] . '.signKeyResend';
                    if (!$this->acquireSignKeyResendLock($cooldownKey, $cooldown)) {
                        // 冷却期内设备重复请求认证，可能是上一次signKey下发失败/未消费。
                        // 改进：不得只返回字符串（MQ单向场景设备收不到），
                        // 而应复用已有signKey强制重发，确保设备能拿到key完成认证。
                        $resendKey = $this->machine['machine_id'] . '.signKeyForceResend';
                        $forceCount = intval(cache($resendKey) ?: 0);
                        if ($forceCount < $this->signKeyForceResendThreshold) {
                            cache($resendKey, $forceCount + 1, $cooldown);
                            actionLog([
                                'machine_id' => $this->machine['machine_id'],
                                'cooldown' => $cooldown,
                                'force_resend' => $forceCount + 1,
                            ], 'signKey重发已限流，复用已有Key强制重发', "setSignKey");
                            // 复用缓存/数据库中的signKey重新投递MQ，保证设备能收到
                            $resendSignKey = $this->machine['signKey'] ?: ($signKey ?: cache($this->machine['machine_id'] . '.signKey'));
                            if ($resendSignKey) {
                                $this->dispatchSignKey($resendSignKey);
                                return $this->r(200, '认证信息已下发，请使用sign重试');
                            }
                        } else {
                            actionLog([
                                'machine_id' => $this->machine['machine_id'],
                                'cooldown' => $cooldown,
                                'force_resend' => $forceCount,
                            ], 'signKey强制重发次数已达上限，停止重发', "setSignKey");
                            return $this->r(200, '认证信息已下发，请使用sign重试');
                        }
                    }
                    $this->dispatchSignKey($signKey);
                    return $this->r(200,'处理成功');
                }
            } catch (\Exception $e) {
                if (isset($cooldownKey)) {
                    $this->releaseSignKeyResendLock($cooldownKey);
                }
                $this->signKeyBootstrapFailed = true;
                actionException($e,1);
                return $this->rTryCatch($e->getMessage());
            }
        }
        return true;
    }

    /**
     * 组装并投递 signKey 到设备 MQ 队列。
     * @param string $signKey
     * @return mixed
     */
    protected function dispatchSignKey($signKey)
    {
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
        actionLog([
            'msg_id' => $data['msg_id'],
            'machine_id' => $data['machine_id'],
            'expires_in' => $data['expires_in'],
        ], '发送signKey至MQ服务器',"setSignKey");
        $this->dataRecord(2, 2, $data);

        actionLog($this->mqQueue,'下发队列名',"setSignKey");
        $result = MqProducer::dataSend($data, $this->mqQueue);
        actionLog($result, '发送结果',"setSignKey");
        if ($result !== true) {
            $cooldownKey = $this->machine['machine_id'] . '.signKeyResend';
            $this->releaseSignKeyResendLock($cooldownKey);
            $this->signKeyBootstrapFailed = true;
            return $this->rTryCatch('下发signKey失败');
        }
        @cache($this->machine['machine_id'] . ".signKey", $signKey, 3600 * 5);
        actionLog(['machine_id' => $this->machine['machine_id']], '设备signKey已缓存',"setSignKey");
        return true;
    }

    /**
     * 优先使用 Redis SET NX EX 原子限流，其他缓存驱动保持兼容降级。
     */
    protected function acquireSignKeyResendLock($key, $ttl)
    {
        try {
            $store = Cache::store();
            $handler = $store->handler();
            $cacheKey = $store->getCacheKey($key);
            if (class_exists('Redis') && $handler instanceof \Redis) {
                return (bool)$handler->set($cacheKey, 1, ['nx', 'ex' => $ttl]);
            }
            if (class_exists('Predis\\Client') && $handler instanceof \Predis\Client) {
                return (string)$handler->set($cacheKey, 1, 'EX', $ttl, 'NX') === 'OK';
            }
            if ($store->has($key)) {
                return false;
            }
            return $store->set($key, 1, $ttl);
        } catch (\Throwable $e) {
            actionException($e, 1);
            throw new \RuntimeException('signKey限流锁获取失败', 0, $e);
        }
    }

    protected function releaseSignKeyResendLock($key)
    {
        try {
            return Cache::store()->delete($key);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return false;
        }
    }

    /**
     * MQ 无签名消息只能完成认证握手，不能继续执行业务。
     */
    public function isSignKeyBootstrapHandled()
    {
        return $this->signKeyBootstrapHandled;
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