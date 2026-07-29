<?php

namespace app\AppFactory\Wx\Official;

use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\Wx\WxBaseClient;
use think\facade\Cache;
use think\facade\Cookie;
use think\facade\Session;

class CouponClient extends WxBaseClient
{
    use ActivityCouponTrait, ActivityCouponUsedTrait, ActivityGoodsTrait, ActivityMachineTrait, WxOfficialTrait;

    const CACHE_EXPIRE = 300;
    const CACHE_SUFFIX = 'url_coupon';
    const SESSION_KEY = 'wx_coupon_receive_key';
    const VISITOR_SESSION_KEY = 'wx_coupon_visitor_id';
    const VISITOR_COOKIE_KEY = 'wx_coupon_visitor';
    const CAPTCHA_EXPIRE = 120;
    const RATE_KEY_MINUTE_LIMIT = 10;
    const RATE_COUPON_MINUTE_LIMIT = 30;
    const RATE_IP_DAY_LIMIT = 50;

    public function authorize($postData)
    {
        $ticket = trim(strval($postData['ticket'] ?? ''));
        if (!$this->isValidTicket($ticket)) showMsg('优惠券领取链接参数错误');

        $coupon = $this->getCouponByTicket($ticket);
        if (!$coupon) showMsg('优惠券领取链接不存在');

        if (intval($coupon['need_oauth'] ?? 1) === 0) {
            return $this->redirectAnonymousCoupon($coupon, $ticket);
        }

        $wxApp = $this->getDefaultWxApp();
        if (!$wxApp) showMsg('查无可用的微信公众号配置');

        $host = rtrim(strval(env('app.host')), '/');
        if (!$host) showMsg('系统域名未配置');
        $callbackUrl = $host . '/wx/coupon/callback?' . http_build_query(['ticket' => $ticket]);
        $redirectUrl = $wxApp->oauth->scopes(['snsapi_base'])->redirect($callbackUrl);
        header('Location: ' . $redirectUrl);
        die();
    }

    public function callback($postData)
    {
        $ticket = trim(strval($postData['ticket'] ?? ''));
        $code = trim(strval($postData['code'] ?? ''));
        if (!$this->isValidTicket($ticket) || !$code) showMsg('微信授权回调参数错误');

        $coupon = $this->getCouponByTicket($ticket);
        if (!$coupon) showMsg('优惠券领取链接不存在');

        try {
            $wxApp = $this->getDefaultWxApp();
            if (!$wxApp) showMsg('查无可用的微信公众号配置');
            $user = $wxApp->oauth->userFromCode($code);
            $response = $user->getTokenResponse();
            $openid = trim(strval($response['openid'] ?? ''));
            if (!$openid) showMsg('微信授权失败，未获取到openid');

            $key = $this->createReceiveKey($openid, intval($coupon['c_id']), $ticket, 'openid');

            $host = rtrim(strval(env('app.host')), '/');
            if (!$host) showMsg('系统域名未配置');
            $pageUrl = $host . '/wx/coupon/page?' . http_build_query(['key' => $key]);
            actionLog([
                'c_id' => intval($coupon['c_id']),
                'openid' => $openid,
                'key' => $key,
                'url' => $pageUrl,
            ], '优惠券静默授权成功，跳转领取页面');
            return redirect($pageUrl);
        } catch (\Exception $e) {
            actionException($e, 1);
            showMsg('微信授权失败，请重新打开优惠券领取链接');
        }
    }

    public function captcha($postData)
    {
        if (!request()->isPost()) return $this->r(100, '仅支持POST请求');
        $key = trim(strval($postData['key'] ?? ''));
        $credential = $this->getReceiveCredential($key);
        if (is_string($credential)) return $this->r(100, $credential);
        $rateCheck = $this->checkReceiveRateLimit($key, intval($credential['coupon_id']));
        if ($rateCheck !== true) return $this->r(100, $rateCheck);

        try {
            $captcha = $this->getCaptchaService()->get();
            $token = trim(strval($captcha['token'] ?? ''));
            if (!$token) return $this->r(100, '滑块验证码生成失败，请重试');

            Cache::set(
                $this->getCaptchaChallengeCacheKey($token),
                $this->getCaptchaBindingValue($key),
                self::CAPTCHA_EXPIRE
            );
            return $this->r(200, '获取成功', [
                'token' => $token,
                'originalImageBase64' => strval($captcha['originalImageBase64'] ?? ''),
                'jigsawImageBase64' => strval($captcha['jigsawImageBase64'] ?? ''),
            ]);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->r(100, '滑块验证码生成失败，请重试');
        }
    }

    public function captchaCheck($postData)
    {
        if (!request()->isPost()) return $this->r(100, '仅支持POST请求');
        $key = trim(strval($postData['key'] ?? ''));
        $credential = $this->getReceiveCredential($key);
        if (is_string($credential)) return $this->r(100, $credential);

        $token = trim(strval($postData['token'] ?? ''));
        $x = intval($postData['x'] ?? -1);
        if (!preg_match('/^[a-f0-9-]{36}$/', $token) || $x < 0 || $x > 1000) {
            return $this->r(100, '滑块验证码参数错误，请重试');
        }

        $challengeCacheKey = $this->getCaptchaChallengeCacheKey($token);
        $challengeBinding = strval(Cache::get($challengeCacheKey, ''));
        Cache::delete($challengeCacheKey);
        if (
            !$challengeBinding
            || !hash_equals($challengeBinding, $this->getCaptchaBindingValue($key))
        ) {
            return $this->r(100, '滑块验证码已失效，请重试');
        }

        try {
            $verification = strval($this->getCaptchaService()->checkPlainPoint($token, $x));
            if (!$verification) return $this->r(100, '滑块验证失败，请重试');
            Cache::set(
                $this->getCaptchaVerificationCacheKey($verification),
                $this->getCaptchaBindingValue($key),
                self::CAPTCHA_EXPIRE
            );
            return $this->r(200, '验证成功', ['captchaVerification' => $verification]);
        } catch (\Exception $e) {
            return $this->r(100, '滑块位置不正确，请重试');
        }
    }

    public function receive($postData)
    {
        if (!request()->isPost()) return $this->r(100, '仅支持POST请求');
        $key = trim(strval($postData['key'] ?? ''));
        $credential = $this->getReceiveCredential($key);
        if (is_string($credential)) return $this->r(100, $credential);
        $openid = strval($credential['identity']);
        $couponId = intval($credential['coupon_id']);

        $captchaVerification = trim(strval($postData['captchaVerification'] ?? ''));
        $captchaCheck = $this->verifyCaptchaVerification($key, $captchaVerification);
        if ($captchaCheck !== true) return $this->r(100, $captchaCheck);

        $coupon = $this->getActivityCouponFind(['c_id' => $couponId]);
        if (!$coupon) return $this->r(100, '查无优惠券信息');
        $coupon = $coupon->toArray();
        $check = $this->checkCoupon($coupon);
        if ($check !== true) return $this->r(100, $check);
        $check = $this->checkReceiveRule($coupon, $openid);
        if ($check !== true) return $this->r(100, $check);

        try {
            $code = $this->generateCouponCode();
            if (!$code) return $this->r(100, '优惠码生成失败，请稍后重试');
            $insert = [
                'c_id' => intval($coupon['c_id']),
                'openid' => $openid,
                'code' => $code,
                'code_type' => 1,
                'receive_type' => 2,
                'status' => 1,
                'c_type' => intval($coupon['c_type']),
                'pay_limit' => $coupon['pay_limit'],
                'reduction' => $coupon['reduction'],
            ];
            $couponUsedId = $this->addActivityCouponUsed($insert);
            if (!$couponUsedId) return $this->r(100, '优惠券领取失败，请稍后重试');
            $receiveTime = time();
            $nextCheck = $this->checkReceiveRule($coupon, $openid);
            actionLog([
                'cu_id' => $couponUsedId,
                'c_id' => intval($coupon['c_id']),
                'openid' => $openid,
                'code' => $code,
                'ip' => request()->ip(),
            ], '通过链接领取优惠券成功');
            return $this->r(200, '领取成功', [
                'code' => $code,
                'couponCode' => $this->formatCouponCode([
                    'cu_id' => $couponUsedId,
                    'code' => $code,
                    'status' => 1,
                    'create_time' => $receiveTime,
                ]),
                'canReceive' => $nextCheck === true,
                'receiveMessage' => $nextCheck === true ? '' : strval($nextCheck),
            ]);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取服务端模板页需要的优惠券展示数据
     * @param array $postData
     * @return array
     */
    public function getPageData($postData)
    {
        $key = trim(strval($postData['key'] ?? ''));
        $credential = $this->getReceiveCredential($key);
        if (is_string($credential)) return $this->pageError($credential);
        $openid = strval($credential['identity']);
        $couponId = intval($credential['coupon_id']);

        $coupon = $this->getActivityCouponFind(['c_id' => $couponId]);
        if (!$coupon) return $this->pageError('查无优惠券信息');
        $coupon = $coupon->toArray();
        $check = $this->checkCoupon($coupon);
        if ($check === true) {
            $check = $this->checkReceiveRule($coupon, $openid);
        }

        return array_merge([
            'pageSuccess' => true,
            'pageMessage' => '',
            'key' => $key,
            'canReceive' => $check === true,
            'receiveMessage' => $check === true ? '' : strval($check),
            'couponCodes' => $this->getCouponCodeList(intval($coupon['c_id']), $openid),
        ], $this->formatCouponPageData($coupon), $this->formatCouponScopeData($coupon));
    }

    protected function formatCouponScopeData($coupon)
    {
        $couponId = intval($coupon['c_id']);
        $where = ['a_id' => $couponId, 'a_type' => 1];
        $machineType = intval($coupon['designated_machine'] ?? 1);
        $goodsType = intval($coupon['designated_goods'] ?? 1);
        $machineScopeList = [];
        $goodsScopeList = [];

        if ($machineType === 2) {
            $machineList = $this->getActivityMachineList(
                $where,
                0,
                'am_id,m_id,machine_id,machine_name',
                'am_id asc'
            );
            if ($machineList) {
                foreach ($machineList->toArray() as $machine) {
                    $machineId = trim(strval($machine['machine_id'] ?? ''));
                    $machineScopeList[] = [
                        'name' => trim(strval($machine['machine_name'] ?? '')) ?: ($machineId ?: '未命名设备'),
                        'subText' => $machineId ? '设备编号：' . $machineId : '',
                    ];
                }
            }
        }

        if (in_array($goodsType, [2, 3], true)) {
            $goodsList = $this->getActivityGoodsList(
                $where,
                0,
                'ag_id,g_id,g_name,sku',
                'ag_id asc'
            );
            if ($goodsList) {
                foreach ($goodsList->toArray() as $goods) {
                    $sku = trim(strval($goods['sku'] ?? ''));
                    $goodsScopeList[] = [
                        'name' => trim(strval($goods['g_name'] ?? '')) ?: '未命名商品',
                        'subText' => $sku ? 'SKU：' . $sku : '',
                    ];
                }
            }
        }

        $machineCount = count($machineScopeList);
        $goodsCount = count($goodsScopeList);
        if ($goodsType === 2) {
            $goodsScopeLabel = '适用商品';
            $goodsScopeText = '指定商品（' . $goodsCount . '款）';
            $goodsScopeTitle = '适用商品';
            $goodsScopeHint = '以下商品可以使用该优惠券';
        } elseif ($goodsType === 3) {
            $goodsScopeLabel = '不适用商品';
            $goodsScopeText = '排除商品（' . $goodsCount . '款）';
            $goodsScopeTitle = '以下商品不可使用';
            $goodsScopeHint = '除以下商品外，其他商品可以使用该优惠券';
        } else {
            $goodsScopeLabel = '适用商品';
            $goodsScopeText = '全部商品';
            $goodsScopeTitle = '';
            $goodsScopeHint = '';
        }

        return [
            'machineScopeText' => $machineType === 2 ? '指定设备（' . $machineCount . '台）' : '全部设备',
            'machineScopeClickable' => $machineType === 2 && $machineCount > 0,
            'machineScopeTitle' => '适用设备',
            'machineScopeHint' => '该优惠券仅可在以下设备使用',
            'machineScopeList' => $machineScopeList,
            'goodsScopeLabel' => $goodsScopeLabel,
            'goodsScopeText' => $goodsScopeText,
            'goodsScopeClickable' => in_array($goodsType, [2, 3], true) && $goodsCount > 0,
            'goodsScopeTitle' => $goodsScopeTitle,
            'goodsScopeHint' => $goodsScopeHint,
            'goodsScopeList' => $goodsScopeList,
        ];
    }

    protected function getCouponCodeList($couponId, $openid)
    {
        $list = $this->getActivityCouponUsedList([
            'c_id' => intval($couponId),
            'openid' => strval($openid),
        ], 0, 'cu_id,code,status,create_time,used_time', 'create_time desc,cu_id desc');
        if (!$list) return [];

        $result = [];
        foreach ($list->toArray() as $item) {
            $result[] = $this->formatCouponCode($item);
        }
        return $result;
    }

    protected function formatCouponCode($item)
    {
        $status = intval($item['status'] ?? 0);
        $statusMap = [
            1 => ['text' => '未使用', 'class' => 'unused'],
            2 => ['text' => '已使用', 'class' => 'used'],
            3 => ['text' => '已过期', 'class' => 'expired'],
            4 => ['text' => '已作废', 'class' => 'invalid'],
        ];
        $statusInfo = $statusMap[$status] ?? ['text' => '未知状态', 'class' => 'unknown'];
        $createTime = $item['create_time'] ?? 0;
        $createTimestamp = is_numeric($createTime) ? intval($createTime) : intval(strtotime(strval($createTime)));

        return [
            'cuId' => intval($item['cu_id'] ?? 0),
            'code' => strval($item['code'] ?? ''),
            'status' => $status,
            'statusText' => $statusInfo['text'],
            'statusClass' => $statusInfo['class'],
            'receiveTime' => $createTimestamp > 0 ? date('Y-m-d H:i:s', $createTimestamp) : '--',
        ];
    }

    protected function getCouponByTicket($ticket)
    {
        $coupon = $this->getActivityCouponFind(['ticket' => $ticket]);
        return $coupon ? $coupon->toArray() : [];
    }

    protected function getDefaultWxApp()
    {
        $wx = $this->getWxOfficialFind(['status' => 1], '*', 'id asc');
        if (!$wx) return false;
        return $this->getWxApp($wx->toArray());
    }

    protected function isValidTicket($ticket)
    {
        return boolval(preg_match('/^[a-f0-9]{32}$/', $ticket));
    }

    protected function redirectAnonymousCoupon($coupon, $ticket)
    {
        $visitorId = trim(strval(Session::get(self::VISITOR_SESSION_KEY, '')));
        if (!preg_match('/^[a-f0-9]{32}$/', $visitorId)) {
            $visitorId = $this->getVisitorIdFromCookie();
        }
        if (!preg_match('/^[a-f0-9]{32}$/', $visitorId)) {
            $visitorId = bin2hex(random_bytes(16));
        }
        Session::set(self::VISITOR_SESSION_KEY, $visitorId);
        Cookie::set(self::VISITOR_COOKIE_KEY, $visitorId . '.' . $this->signVisitorId($visitorId), [
            'expire' => 31536000,
            'path' => '/',
            'secure' => request()->isSsl(),
            'httponly' => true,
            'samesite' => 'lax',
        ]);

        $identity = 'anonymous_' . $visitorId;
        $key = $this->createReceiveKey(
            $identity,
            intval($coupon['c_id']),
            $ticket,
            'anonymous'
        );
        $host = rtrim(strval(env('app.host')), '/');
        if (!$host) showMsg('系统域名未配置');
        $pageUrl = $host . '/wx/coupon/page?' . http_build_query(['key' => $key]);
        actionLog([
            'c_id' => intval($coupon['c_id']),
            'identity' => $identity,
            'key' => $key,
            'url' => $pageUrl,
        ], '优惠券无需静默授权，跳转领取页面');
        return redirect($pageUrl);
    }

    protected function getVisitorIdFromCookie()
    {
        $cookieValue = trim(strval(Cookie::get(self::VISITOR_COOKIE_KEY, '')));
        $parts = explode('.', $cookieValue, 2);
        $visitorId = trim(strval($parts[0] ?? ''));
        $signature = trim(strval($parts[1] ?? ''));
        if (
            !preg_match('/^[a-f0-9]{32}$/', $visitorId)
            || !preg_match('/^[a-f0-9]{64}$/', $signature)
            || !hash_equals($this->signVisitorId($visitorId), $signature)
        ) {
            return '';
        }
        return $visitorId;
    }

    protected function signVisitorId($visitorId)
    {
        $secret = strval(config('app.salt')) . '|' . self::CACHE_SUFFIX;
        return hash_hmac('sha256', strval($visitorId), $secret);
    }

    protected function createReceiveKey($identity, $couponId, $ticket, $identityType)
    {
        $key = md5(
            strval($identity)
            . strval($ticket)
            . microtime(true)
            . bin2hex(random_bytes(16))
            . self::CACHE_SUFFIX
        );
        cache($key, [
            'identity' => strval($identity),
            'coupon_id' => intval($couponId),
            'identity_type' => strval($identityType),
            'session_hash' => $this->getSessionHash(),
        ], ['expire' => self::CACHE_EXPIRE]);
        Session::set(self::SESSION_KEY, $key);
        return $key;
    }

    protected function getReceiveCredential($key)
    {
        if (!preg_match('/^[a-f0-9]{32}$/', strval($key))) {
            return '领取凭证参数错误，请重新打开优惠券领取链接';
        }
        if (!$this->isSessionKeyValid($key)) {
            return '打开方式有误，请重新打开领取链接';
        }

        $cacheValue = cache($key);
        if (!$cacheValue) {
            return '领取凭证已过期，请重新打开优惠券领取链接';
        }

        // 兼容发布前已经生成、尚未过期的旧版字符串缓存。
        if (is_string($cacheValue)) {
            $valueParts = explode('&', $cacheValue, 2);
            $cacheValue = [
                'identity' => trim(strval($valueParts[0] ?? '')),
                'coupon_id' => intval($valueParts[1] ?? 0),
                'identity_type' => 'openid',
                'session_hash' => '',
            ];
        }
        if (!is_array($cacheValue)) return '领取凭证数据错误';

        $identity = trim(strval($cacheValue['identity'] ?? ''));
        $couponId = intval($cacheValue['coupon_id'] ?? 0);
        $sessionHash = trim(strval($cacheValue['session_hash'] ?? ''));
        if (!$identity || !$couponId) return '领取凭证数据错误';
        if ($sessionHash && !hash_equals($sessionHash, $this->getSessionHash())) {
            return '打开方式有误，请重新打开领取链接';
        }

        return [
            'identity' => $identity,
            'coupon_id' => $couponId,
            'identity_type' => strval($cacheValue['identity_type'] ?? 'openid'),
        ];
    }

    protected function isSessionKeyValid($key)
    {
        $sessionKey = trim(strval(Session::get(self::SESSION_KEY, '')));
        return $sessionKey !== '' && hash_equals($sessionKey, strval($key));
    }

    protected function getSessionHash()
    {
        return hash('sha256', strval(Session::getId()));
    }

    protected function getCaptchaService()
    {
        $config = require dirname(__DIR__, 4) . '/extend/Fastknife/config.php';
        $config['watermark'] = [];
        $config['cache']['constructor'] = Cache::store();
        $config['cache']['options'] = ['expire' => self::CAPTCHA_EXPIRE];
        return new CouponSliderCaptchaService($config);
    }

    protected function getCaptchaBindingValue($key)
    {
        return hash('sha256', strval($key) . '|' . $this->getSessionHash());
    }

    protected function getCaptchaChallengeCacheKey($token)
    {
        return 'wx_coupon_captcha_challenge_' . md5(strval($token));
    }

    protected function getCaptchaVerificationCacheKey($verification)
    {
        return 'wx_coupon_captcha_verification_' . md5(strval($verification));
    }

    protected function verifyCaptchaVerification($key, $verification)
    {
        if (!$verification || strlen($verification) > 2048) {
            return '请先完成滑块验证';
        }
        $cacheKey = $this->getCaptchaVerificationCacheKey($verification);
        $binding = strval(Cache::get($cacheKey, ''));
        if (!$binding || !hash_equals($binding, $this->getCaptchaBindingValue($key))) {
            return '滑块验证已失效，请重新验证';
        }

        // 先删除业务绑定，再执行组件的一次性二次校验，阻止重复提交。
        Cache::delete($cacheKey);
        try {
            $this->getCaptchaService()->verificationByEncryptCode($verification);
        } catch (\Exception $e) {
            return '滑块验证已失效，请重新验证';
        }
        return true;
    }

    protected function checkReceiveRateLimit($key, $couponId)
    {
        $ip = request()->ip();
        if (!filter_var($ip, FILTER_VALIDATE_IP)) $ip = '0.0.0.0';
        $dayBucket = date('Ymd');
        $tomorrow = strtotime('tomorrow');
        $rules = [
            [
                'key' => 'wx_coupon_rate_key_' . md5($ip . '|' . $key),
                'ttl' => 60,
                'limit' => self::RATE_KEY_MINUTE_LIMIT,
                'message' => '操作过于频繁，请1分钟后重试',
            ],
            [
                'key' => 'wx_coupon_rate_coupon_' . md5($ip . '|' . intval($couponId)),
                'ttl' => 60,
                'limit' => self::RATE_COUPON_MINUTE_LIMIT,
                'message' => '当前网络领取过于频繁，请稍后重试',
            ],
            [
                'key' => 'wx_coupon_rate_ip_day_' . md5($ip . '|' . $dayBucket),
                'ttl' => max(60, $tomorrow - time() + 60),
                'limit' => self::RATE_IP_DAY_LIMIT,
                'message' => '当前网络今日领取次数已达上限',
            ],
        ];

        $blockedMessage = '';
        foreach ($rules as $rule) {
            $count = $this->increaseRateCounter($rule['key'], $rule['ttl']);
            if (!$blockedMessage && $count > $rule['limit']) {
                $blockedMessage = $rule['message'];
            }
        }
        if ($blockedMessage) {
            actionLog([
                'ip' => $ip,
                'coupon_id' => intval($couponId),
                'key' => strval($key),
                'message' => $blockedMessage,
            ], '优惠券链接领取触发限流');
            return $blockedMessage;
        }
        return true;
    }

    protected function increaseRateCounter($cacheKey, $ttl)
    {
        if (!Cache::has($cacheKey)) {
            Cache::set($cacheKey, 1, intval($ttl));
            return 1;
        }
        return intval(Cache::store()->inc($cacheKey));
    }

    protected function checkCoupon($coupon)
    {
        if (!$coupon) return '查无优惠券信息';
        if (!empty($coupon['code'])) return '固定码优惠券不支持链接领取';
        if (intval($coupon['url_coupon_count'] ?? 0) <= 0) return '优惠券链接累计领取数量配置错误';
        if (intval($coupon['url_day_count'] ?? 0) < 0) return '优惠券链接领取间隔配置错误';
        if (!in_array(intval($coupon['status']), [1, 2])) return '优惠券已过期或已作废';
        $now = time();
        if (intval($coupon['start_date']) > $now) return '优惠券活动尚未开始';
        if (intval($coupon['end_date']) > 0 && intval($coupon['end_date']) < $now) {
            $this->updateActivityCoupon(['c_id' => intval($coupon['c_id']), 'status' => 3]);
            $this->updateActivityCouponUsed(['status' => 3], [
                'c_id' => intval($coupon['c_id']),
                'status' => 1,
            ]);
            return '优惠券活动已结束';
        }
        if (intval($coupon['status']) === 1) {
            $this->updateActivityCoupon(['c_id' => intval($coupon['c_id']), 'status' => 2]);
        }
        return true;
    }

    protected function checkReceiveRule($coupon, $openid)
    {
        $where = [
            'c_id' => intval($coupon['c_id']),
            'openid' => $openid,
        ];
        $receivedCount = $this->getActivityCouponUsedCount($where);
        if ($receivedCount >= intval($coupon['url_coupon_count'])) {
            return '您领取该优惠券的数量已达到上限';
        }

        $lastRecord = $this->getActivityCouponUsedFind($where, 'cu_id,create_time', 'create_time desc');
        $intervalDays = intval($coupon['url_day_count'] ?? 0);
        if ($lastRecord && $intervalDays > 0) {
            $lastDayStart = strtotime(date('Y-m-d', intval($lastRecord['create_time'])));
            $nextReceiveTime = strtotime('+' . $intervalDays . ' day', $lastDayStart);
            if (time() < $nextReceiveTime) {
                return '暂未到下一次领取日期，可领取时间：' . date('Y-m-d H:i:s', $nextReceiveTime);
            }
        }
        return true;
    }

    protected function generateCouponCode()
    {
        $codeList = $this->getActivityCouponUsedColumn(['status' => 1], 'code');
        $fixedCodeList = $this->getActivityCouponColumn([['status', 'in', [1, 2]]], 'code');
        $codeList = array_map('strval', array_merge($codeList, $fixedCodeList));
        for ($i = 0; $i < 10; $i++) {
            $code = $this->generateRandomCouponCode();
            if (!in_array($code, $codeList, true)) return $code;
        }
        return '';
    }

    /**
     * 生成优惠券链接领取专用的8位数字随机码（允许0开头）
     * @return string
     */
    protected function generateRandomCouponCode()
    {
        $characters = '0123456789';
        $maxIndex = strlen($characters) - 1;
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[random_int(0, $maxIndex)];
        }
        return $code;
    }

    protected function pageError($message)
    {
        return [
            'pageSuccess' => false,
            'pageMessage' => strval($message),
            'key' => '',
            'couponName' => '优惠券领取',
            'couponLogo' => '',
            'slogan' => '专属优惠，立即领取',
        ];
    }

    protected function formatCouponPageData($coupon)
    {
        $couponType = intval($coupon['c_type']);
        $goodsType = intval($coupon['designated_goods']);
        $reduction = floatval($coupon['reduction']);
        $payLimit = floatval($coupon['pay_limit']);
        $thresholdText = $payLimit > 0
            ? '满' . $this->formatNumber($payLimit) . '元可用'
            : '无门槛使用';

        if ($couponType === 2) {
            $discountRate = max(0, 100 - $reduction);
            $discountValue = $this->formatNumber($discountRate / 10);
            $heroPrefix = '';
            $heroValue = $discountValue;
            $heroSuffix = '折';
            $goodsScopeTitle = $goodsType === 2 ? '指定商品' : ($goodsType === 3 ? '部分商品' : '全场商品');
            $heroTitle = $goodsScopeTitle . $discountValue . '折';
            $benefitLabel = '折扣力度';
            $benefitValue = $discountValue . '折（立享' . $this->formatNumber($reduction) . '%优惠）';
        } else {
            $heroPrefix = '¥';
            $heroValue = $this->formatNumber($reduction);
            $heroSuffix = '';
            $heroTitle = $thresholdText;
            $benefitLabel = '优惠金额';
            $benefitValue = $this->formatNumber($reduction) . '元';
        }

        if ($goodsType === 2) {
            $defaultDescription = '指定商品可用';
        } elseif ($goodsType === 3) {
            $defaultDescription = '全场商品可用，部分特殊商品除外';
        } else {
            $defaultDescription = '全场商品可用';
        }

        $description = trim(strval($coupon['desc'] ?? '')) ?: $defaultDescription;
        $descriptionZhCn = $description;
        $descriptionZhHk = $description;
        $descriptionEnUs = $description;
        $descriptionI18n = json_decode($description, true);
        if (is_array($descriptionI18n)) {
            $normalizedDescriptions = [];
            foreach ($descriptionI18n as $language => $content) {
                $language = strtolower(str_replace('_', '-', trim(strval($language))));
                $normalizedDescriptions[$language] = trim(strval($content));
            }
            $descriptionZhCn = $normalizedDescriptions['zh-cn'] ?? '';
            $descriptionZhHk = $normalizedDescriptions['zh-hk'] ?? '';
            $descriptionEnUs = $normalizedDescriptions['en-us'] ?? ($normalizedDescriptions['en'] ?? '');
            if (!$descriptionZhCn) $descriptionZhCn = $descriptionZhHk ?: $defaultDescription;
            if (!$descriptionZhHk) $descriptionZhHk = $descriptionZhCn ?: $defaultDescription;
            if (!$descriptionEnUs) $descriptionEnUs = $descriptionZhCn ?: $defaultDescription;
            $description = $descriptionZhCn;
        }
        $startDate = intval($coupon['start_date']) > 0 ? date('Y.m.d', intval($coupon['start_date'])) : '即日起';
        $endDate = intval($coupon['end_date']) > 0 ? date('Y.m.d', intval($coupon['end_date'])) : '长期有效';

        return [
            'couponName' => strval($coupon['c_name'] ?? '专属优惠券'),
            'couponLogo' => trim(strval($coupon['coupon_logo'] ?? '')),
            'slogan' => trim(strval($coupon['slogan'] ?? '')) ?: '专属优惠，立即领取',
            'heroPrefix' => $heroPrefix,
            'heroValue' => $heroValue,
            'heroSuffix' => $heroSuffix,
            'heroTitle' => $heroTitle,
            'heroSubtitle' => $couponType === 2 ? $thresholdText : '',
            'benefitLabel' => $benefitLabel,
            'benefitValue' => $benefitValue,
            'thresholdText' => $thresholdText,
            'validDate' => $startDate . ' - ' . $endDate,
            'description' => $description,
            'descriptionZhCn' => $descriptionZhCn,
            'descriptionZhHk' => $descriptionZhHk,
            'descriptionEnUs' => $descriptionEnUs,
            'limitText' => '每个用户限领' . intval($coupon['url_coupon_count']) . '张',
        ];
    }

    protected function formatNumber($number)
    {
        return rtrim(rtrim(number_format(floatval($number), 2, '.', ''), '0'), '.');
    }
}
