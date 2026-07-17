<?php

namespace app\AppFactory\Wx\Official;

use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\Wx\WxBaseClient;

class CouponClient extends WxBaseClient
{
    use ActivityCouponTrait, ActivityCouponUsedTrait, WxOfficialTrait;

    const CACHE_EXPIRE = 300;
    const CACHE_SUFFIX = 'url_coupon';

    public function authorize($postData)
    {
        $ticket = trim(strval($postData['ticket'] ?? ''));
        if (!$this->isValidTicket($ticket)) showMsg('优惠券领取链接参数错误');

        $coupon = $this->getCouponByTicket($ticket);
        if (!$coupon) showMsg('优惠券领取链接不存在');

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

            $timestamp = time();
            $key = md5($openid . $ticket . $timestamp . self::CACHE_SUFFIX);
            cache($key, $openid . '&' . intval($coupon['c_id']), ['expire' => self::CACHE_EXPIRE]);

            $host = rtrim(strval(env('app.host')), '/');
            if (!$host) showMsg('系统域名未配置');
            $pageUrl = $host . '/wx/coupon/page?' . http_build_query(['key' => $key]);
            actionLog([
                'c_id' => intval($coupon['c_id']),
                'openid' => $openid,
                'key' => $key,
                'url' => $pageUrl,
            ], '优惠券静默授权成功，跳转领取页面');
            header('Location: ' . $pageUrl);
            die();
        } catch (\Exception $e) {
            actionException($e, 1);
            showMsg('微信授权失败，请重新打开优惠券领取链接');
        }
    }

    public function receive($postData)
    {
        if (!request()->isPost()) return $this->r(100, '仅支持POST请求');
        $key = trim(strval($postData['key'] ?? ''));
        if (!preg_match('/^[a-f0-9]{32}$/', $key)) return $this->r(100, '领取凭证参数错误');

        $cacheValue = strval(cache($key));
        if (!$cacheValue) return $this->r(100, '领取凭证已过期，请重新打开优惠券领取链接');
        $valueParts = explode('&', $cacheValue, 2);
        $openid = trim(strval($valueParts[0] ?? ''));
        $couponId = intval($valueParts[1] ?? 0);
        if (!$openid || !$couponId) return $this->r(100, '领取凭证数据错误');

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
        if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
            return $this->pageError('领取凭证参数错误，请重新打开优惠券领取链接');
        }

        $cacheValue = strval(cache($key));
        if (!$cacheValue) {
            return $this->pageError('领取凭证已过期，请重新打开优惠券领取链接');
        }
        $valueParts = explode('&', $cacheValue, 2);
        $openid = trim(strval($valueParts[0] ?? ''));
        $couponId = intval($valueParts[1] ?? 0);
        if (!$openid || !$couponId) return $this->pageError('领取凭证数据错误');

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
        ], $this->formatCouponPageData($coupon));
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
        if ($lastRecord) {
            $intervalDays = max(1, intval($coupon['url_day_count']));
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
        for ($i = 0; $i < 100; $i++) {
            $code = $this->generateRandomCouponCode();
            if (!in_array($code, $codeList, true)) return $code;
        }
        return '';
    }

    /**
     * 生成优惠券链接领取专用的8位随机码
     * @return string
     */
    protected function generateRandomCouponCode()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
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
        ];
    }

    protected function formatCouponPageData($coupon)
    {
        $couponType = intval($coupon['c_type']);
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
            $heroTitle = '全场商品' . $discountValue . '折';
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

        $goodsType = intval($coupon['designated_goods']);
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
