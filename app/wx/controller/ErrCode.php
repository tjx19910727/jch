<?php

namespace app\wx\controller;

use app\AppFactory\Kernel\Service\FaultNotice\FaultOrderVideoService;
use app\AppFactory\Kernel\Service\FaultNotice\FaultShutdownService;
use EasyWeChat\Factory;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

/**
 * 微信故障详情页。
 *
 * 详情链接不过期；通过微信静默授权获取点击用户 OpenID，
 * 仅当其与 wx_template_log.openid 一致时允许查看。
 */
class ErrCode
{
    const CONFIRM_VALID_SECONDS = 86400;
    const ACTION_TOKEN_SECONDS = 600;
    const SESSION_PREFIX = 'wx_fault_detail_authorized_';
    const ACTION_TOKEN_SESSION_PREFIX = 'wx_fault_action_token_';
    const ACTION_TOKEN_CACHE_PREFIX = 'wx_fault_action_token:';

    /**
     * 故障详情链接入口，发起 snsapi_base 静默授权。
     */
    public function authorize()
    {
        $check = $this->validateLink(input());
        if (!$check['success']) {
            return $this->renderError($check['message']);
        }

        $log = $check['log'];
        $wx = $this->getWxOfficial(intval($log['wx_id']));
        if (!$wx) {
            return $this->renderError('微信公众号配置不存在，暂时无法查看故障详情');
        }
        $host = $this->getHost();
        if ($host === '') {
            return $this->renderError('系统域名未配置，暂时无法查看故障详情');
        }

        try {
            $callbackUrl = $host . '/wx/err_code/callback?' . http_build_query([
                'wtl_id' => intval($log['wtl_id']),
                'sign' => strval(input('sign')),
            ]);
            $redirectUrl = Factory::officialAccount($wx)
                ->oauth
                ->scopes(['snsapi_base'])
                ->redirect($callbackUrl);
            return redirect($redirectUrl);
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultDetailAuthorize');
            return $this->renderError('微信授权失败，请重新打开故障通知');
        }
    }

    /**
     * 静默授权回调，校验点击用户是否为通知接收人。
     */
    public function callback()
    {
        $params = input();
        $check = $this->validateLink($params);
        if (!$check['success']) {
            return $this->renderError($check['message']);
        }

        $code = trim(strval($params['code'] ?? ''));
        if ($code === '') {
            return $this->renderError('微信授权回调参数错误');
        }

        $log = $check['log'];
        $wx = $this->getWxOfficial(intval($log['wx_id']));
        if (!$wx) {
            return $this->renderError('微信公众号配置不存在，暂时无法查看故障详情');
        }

        try {
            $user = Factory::officialAccount($wx)->oauth->userFromCode($code);
            $response = $user->getTokenResponse();
            $openid = trim(strval($response['openid'] ?? ''));
            $receiverOpenid = trim(strval($log['openid'] ?? ''));
            if ($openid === '' || $receiverOpenid === '' || !hash_equals($receiverOpenid, $openid)) {
                Session::delete($this->getSessionKey(intval($log['wtl_id'])));
                Session::delete(self::ACTION_TOKEN_SESSION_PREFIX . intval($log['wtl_id']));
                Cache::delete($this->getActionTokenCacheKey($log));
                return $this->renderError('无权限查看该故障详情');
            }

            Session::set($this->getSessionKey(intval($log['wtl_id'])), $openid);
            $this->issueActionToken($log, $openid);
            $this->confirmWithinValidTime($log);
            return redirect($this->buildPageUrl($log, strval($params['sign'] ?? '')));
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultDetailCallback');
            return $this->renderError('微信授权失败，请重新打开故障通知');
        }
    }

    /**
     * 故障详情 HTML 页面。
     */
    public function index()
    {
        $params = input();
        $check = $this->validateLink($params);
        if (!$check['success']) {
            return $this->renderError($check['message']);
        }

        $log = $check['log'];
        if (!$this->hasAuthorizedSession($log)) {
            return redirect($this->buildAuthorizeUrl($log, strval($params['sign'] ?? '')));
        }
        $actionToken = $this->getValidActionToken($log);
        if ($actionToken === '') {
            return redirect($this->buildAuthorizeUrl($log, strval($params['sign'] ?? '')));
        }

        $this->confirmWithinValidTime($log);
        $event = $this->getFaultEvent($log);
        if (!$event) {
            return $this->renderError('故障事件不存在');
        }

        $errorCode = strval($event['error_code'] ?? '');
        $solutions = Db::name('machine_error_code_solution')
            ->where('error_code', $errorCode)
            ->field('s_id,title,content')
            ->order('create_time asc,s_id asc')
            ->select()
            ->toArray();
        foreach ($solutions as &$solution) {
            $solution['content'] = $this->normalizeSolutionContent(strval($solution['content'] ?? ''));
        }
        unset($solution);

        $showShipmentInfo = strval($log['template_type'] ?? '') === 'mShipmentFailed';
        $videoService = new FaultOrderVideoService();
        $orderInfo = $showShipmentInfo ? $videoService->getOrderInfo($event) : [];
        $videoState = $showShipmentInfo
            ? $videoService->getStatus($event)
            : ['success' => false, 'status' => 'hidden', 'message' => '', 'videos' => []];
        $showShutdownAction = $errorCode === FaultShutdownService::ERROR_CODE;
        $shutdownState = $showShutdownAction
            ? (new FaultShutdownService())->getState($log, $event)
            : ['enabled' => false, 'status' => 'hidden', 'message' => '', 'shutdown_time' => ''];

        View::assign([
            'pageSuccess' => true,
            'pageMessage' => '',
            'machineName' => strval($event['machine_name'] ?? '') ?: '--',
            'machineId' => strval($event['machine_id'] ?? '') ?: '--',
            'showShipmentInfo' => $showShipmentInfo,
            'tradeNo' => trim(strval($event['trade_no'] ?? '')) ?: '--',
            'channelCode' => trim(strval($orderInfo['channel_code'] ?? '')) ?: '--',
            'videoStatus' => strval($videoState['status'] ?? 'idle'),
            'videoMessage' => strval($videoState['message'] ?? ''),
            'orderVideos' => is_array($videoState['videos'] ?? null) ? $videoState['videos'] : [],
            'showShutdownAction' => $showShutdownAction,
            'shutdownEnabled' => !empty($shutdownState['enabled']),
            'shutdownStatus' => strval($shutdownState['status'] ?? ''),
            'shutdownMessage' => strval($shutdownState['message'] ?? ''),
            'shutdownTime' => strval($shutdownState['shutdown_time'] ?? ''),
            'wtlId' => intval($log['wtl_id']),
            'linkSign' => strval($params['sign'] ?? ''),
            'actionToken' => $actionToken,
            'address' => strval($event['address'] ?? '') ?: '--',
            'categoryName' => strval($event['category_name'] ?? '') ?: '设备故障',
            'errorCode' => $errorCode ?: '--',
            'occurTime' => intval($event['create_time'] ?? 0) > 0
                ? date('Y-m-d H:i:s', intval($event['create_time']))
                : '--',
            'description' => trim(strval($event['msg'] ?? ''))
                ?: (trim(strval($event['remark'] ?? '')) ?: strval($event['error_name'] ?? '')),
            'solutions' => $solutions,
        ]);
        return View::fetch('err_code/index');
    }

    /**
     * 向故障设备请求订单视频。
     */
    public function requestOrderVideo()
    {
        if (!request()->isPost()) {
            return $this->jsonResult(false, '请求方式错误');
        }
        $check = $this->validateAuthorizedAction(input());
        if (!$check['success']) {
            return $this->jsonResult(false, $check['message']);
        }
        if (strval($check['log']['template_type'] ?? '') !== 'mShipmentFailed') {
            return $this->jsonResult(false, '该故障通知不支持获取订单视频');
        }
        $result = (new FaultOrderVideoService())->requestVideo($check['event']);
        return $this->jsonResult(!empty($result['success']), strval($result['message'] ?? ''), $result);
    }

    /**
     * 查询订单视频上传状态及视频列表。
     */
    public function getOrderVideoStatus()
    {
        if (!request()->isPost()) {
            return $this->jsonResult(false, '请求方式错误');
        }
        $check = $this->validateAuthorizedAction(input());
        if (!$check['success']) {
            return $this->jsonResult(false, $check['message']);
        }
        if (strval($check['log']['template_type'] ?? '') !== 'mShipmentFailed') {
            return $this->jsonResult(false, '该故障通知不支持获取订单视频');
        }
        $result = (new FaultOrderVideoService())->getStatus($check['event']);
        return $this->jsonResult(!empty($result['success']), strval($result['message'] ?? ''), $result);
    }

    /**
     * 设备未关机故障的远程关机操作。
     */
    public function shutdownMachine()
    {
        if (!request()->isPost()) {
            return $this->jsonResult(false, '请求方式错误');
        }
        $check = $this->validateAuthorizedAction(input());
        if (!$check['success']) {
            return $this->jsonResult(false, $check['message']);
        }
        $result = (new FaultShutdownService())->shutdown($check['log'], $check['event']);
        return $this->jsonResult(!empty($result['success']), strval($result['message'] ?? ''), $result);
    }

    protected function validateAuthorizedAction($params)
    {
        $check = $this->validateLink($params);
        if (!$check['success']) {
            return $check;
        }
        if (!$this->validateActionToken($check['log'], strval($params['action_token'] ?? ''))) {
            return ['success' => false, 'message' => '操作授权已过期，请刷新页面重新授权'];
        }
        if (!$this->hasAuthorizedSession($check['log'])) {
            return ['success' => false, 'message' => '微信授权已失效，请刷新故障详情页后重试'];
        }
        $event = $this->getFaultEvent($check['log']);
        if (!$event) {
            return ['success' => false, 'message' => '故障事件不存在'];
        }
        $check['event'] = $event;
        return $check;
    }

    protected function jsonResult($success, $message, $data = [])
    {
        return json([
            'state' => $success ? 200 : 100,
            'msg' => strval($message),
            'data' => is_array($data) ? $data : [],
        ]);
    }

    protected function validateLink($params)
    {
        $wtlId = intval($params['wtl_id'] ?? 0);
        $sign = trim(strval($params['sign'] ?? ''));
        if ($wtlId <= 0 || $sign === '') {
            return ['success' => false, 'message' => '故障详情链接参数错误'];
        }

        $log = (array)Db::name('wx_template_log')
            ->where('wtl_id', $wtlId)
            ->field(
                'wtl_id,wx_id,manager_id,openid,me_id,m_id,ao_id,error_code,' .
                'template_type,send_status,confirm_status,confirm_time,create_time'
            )
            ->find();
        if (!$log) {
            return ['success' => false, 'message' => '故障通知记录不存在'];
        }
        if (intval($log['send_status'] ?? 0) !== 1) {
            return ['success' => false, 'message' => '故障通知尚未发送成功'];
        }
        if (intval($log['wx_id'] ?? 0) <= 0 || trim(strval($log['openid'] ?? '')) === '') {
            return ['success' => false, 'message' => '故障通知接收人信息不完整'];
        }

        $localSign = $this->makeSign($wtlId, intval($log['wx_id']));
        if (!hash_equals($localSign, $sign)) {
            return ['success' => false, 'message' => '故障详情链接校验失败'];
        }
        return ['success' => true, 'message' => '', 'log' => $log];
    }

    protected function getFaultEvent($log)
    {
        $meId = intval($log['me_id'] ?? 0);
        if ($meId <= 0) {
            return [];
        }
        return (array)Db::name('machine_error_code')
            ->alias('mec')
            ->leftJoin(
                'machine_error_code_notice_rule mecnr',
                'mecnr.error_code = mec.errorCode'
            )
            ->leftJoin(
                'machine_fault_category mfc',
                'mfc.category_id = mec.category_id'
            )
            ->where('mec.me_id', $meId)
            ->where('mec.m_id', intval($log['m_id'] ?? 0))
            ->where('mec.ao_id', intval($log['ao_id'] ?? 0))
            ->where('mec.errorCode', strval($log['error_code'] ?? ''))
            ->field(
                'mec.me_id,mec.m_id,mec.ao_id,mec.machine_id,mec.machine_name,mec.address,' .
                'mec.errorCode AS error_code,mec.trade_no,' .
                'mec.remark,mec.msg,mec.create_time,' .
                "CASE WHEN mec.errorCode='11103021' " .
                "THEN COALESCE(NULLIF(mec.remark,''),NULLIF(mecnr.error_name,''),mec.errorCode) " .
                "ELSE COALESCE(NULLIF(mecnr.error_name,''),NULLIF(mec.remark,''),mec.errorCode) END AS error_name," .
                'mfc.category_name'
            )
            ->find();
    }

    /**
     * 通知创建后24小时内点击才更新确认状态；超时仍可查看详情。
     */
    protected function confirmWithinValidTime($log)
    {
        $createTime = intval($log['create_time'] ?? 0);
        if ($createTime <= 0 || time() > $createTime + self::CONFIRM_VALID_SECONDS) {
            return;
        }
        if (intval($log['confirm_status'] ?? 0) === 1) {
            return;
        }
        Db::name('wx_template_log')
            ->where('wtl_id', intval($log['wtl_id']))
            ->where('confirm_status', '<>', 1)
            ->update([
                'confirm_status' => 1,
                'confirm_time' => time(),
            ]);
    }

    protected function getWxOfficial($wxId)
    {
        return (array)Db::name('wx_official')
            ->where('id', intval($wxId))
            ->find();
    }

    protected function hasAuthorizedSession($log)
    {
        $sessionOpenid = trim(strval(Session::get(
            $this->getSessionKey(intval($log['wtl_id'])),
            ''
        )));
        $receiverOpenid = trim(strval($log['openid'] ?? ''));
        return $sessionOpenid !== ''
            && $receiverOpenid !== ''
            && hash_equals($receiverOpenid, $sessionOpenid);
    }

    protected function getSessionKey($wtlId)
    {
        return self::SESSION_PREFIX . intval($wtlId);
    }

    protected function issueActionToken($log, $openid)
    {
        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Throwable $e) {
            throw new \RuntimeException('操作授权Token生成失败', 0, $e);
        }
        $wtlId = intval($log['wtl_id'] ?? 0);
        $cached = Cache::set($this->getActionTokenCacheKey($log), [
            'token_hash' => hash('sha256', $token),
            'wtl_id' => $wtlId,
            'wx_id' => intval($log['wx_id'] ?? 0),
            'openid' => strval($openid),
        ], self::ACTION_TOKEN_SECONDS);
        if (!$cached) {
            throw new \RuntimeException('操作授权Token写入缓存失败');
        }
        Session::set(self::ACTION_TOKEN_SESSION_PREFIX . $wtlId, $token);
        return $token;
    }

    protected function getValidActionToken($log)
    {
        $token = trim(strval(Session::get(
            self::ACTION_TOKEN_SESSION_PREFIX . intval($log['wtl_id'] ?? 0),
            ''
        )));
        return $this->validateActionToken($log, $token) ? $token : '';
    }

    protected function validateActionToken($log, $token)
    {
        $token = trim(strval($token));
        if ($token === '') {
            return false;
        }
        $cached = Cache::get($this->getActionTokenCacheKey($log));
        if (!is_array($cached)) {
            return false;
        }

        $receiverOpenid = trim(strval($log['openid'] ?? ''));
        $cachedOpenid = trim(strval($cached['openid'] ?? ''));
        return intval($cached['wtl_id'] ?? 0) === intval($log['wtl_id'] ?? 0)
            && intval($cached['wx_id'] ?? 0) === intval($log['wx_id'] ?? 0)
            && $receiverOpenid !== ''
            && $cachedOpenid !== ''
            && hash_equals($receiverOpenid, $cachedOpenid)
            && hash_equals(strval($cached['token_hash'] ?? ''), hash('sha256', $token));
    }

    protected function getActionTokenCacheKey($log)
    {
        return self::ACTION_TOKEN_CACHE_PREFIX
            . intval($log['wtl_id'] ?? 0) . ':'
            . sha1(strval($log['openid'] ?? ''));
    }

    protected function makeSign($wtlId, $wxId)
    {
        $secret = config('app.salt') ?: 'fault_detail_secret';
        return hash('sha256', intval($wtlId) . '|' . intval($wxId) . '|' . $secret);
    }

    protected function buildAuthorizeUrl($log, $sign)
    {
        return $this->getHost() . '/wx/err_code/authorize?' . http_build_query([
            'wtl_id' => intval($log['wtl_id']),
            'sign' => strval($sign),
        ]);
    }

    protected function buildPageUrl($log, $sign)
    {
        return $this->getHost() . '/wx/err_code/index?' . http_build_query([
            'wtl_id' => intval($log['wtl_id']),
            'sign' => strval($sign),
        ]);
    }

    protected function getHost()
    {
        return rtrim(strval(config('app.app_host') ?: env('app.host', '')), '/');
    }

    /**
     * 规范解决方案富文本中的本地资源路径，并增强微信内置浏览器的视频兼容性。
     */
    protected function normalizeSolutionContent($content)
    {
        $content = strval($content);
        if ($content === '') {
            return '';
        }

        $host = $this->getHost();
        $content = preg_replace_callback(
            '/\b(src|poster)\s*=\s*(["\'])(.*?)\2/i',
            function ($matches) use ($host) {
                $url = trim(html_entity_decode(strval($matches[3]), ENT_QUOTES, 'UTF-8'));
                if ($url === ''
                    || preg_match('#^(?:https?:)?//#i', $url)
                    || strpos($url, 'data:') === 0
                    || strpos($url, 'blob:') === 0) {
                    return $matches[0];
                }

                $normalized = preg_replace('#^(?:\.\./|\./)+#', '', $url);
                if (strpos($normalized, 'uploads/') === 0) {
                    $normalized = '/' . $normalized;
                }
                if (strpos($normalized, '/uploads/') === 0 && $host !== '') {
                    $normalized = $host . $normalized;
                }

                return $matches[1] . '=' . $matches[2]
                    . htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8')
                    . $matches[2];
            },
            $content
        );

        return preg_replace_callback('/<video\b([^>]*)>/i', function ($matches) {
            $attributes = strval($matches[1]);
            if (!preg_match('/\bplaysinline\b/i', $attributes)) {
                $attributes .= ' playsinline';
            }
            if (!preg_match('/\bwebkit-playsinline\b/i', $attributes)) {
                $attributes .= ' webkit-playsinline';
            }
            if (!preg_match('/\bpreload\s*=/i', $attributes)) {
                $attributes .= ' preload="metadata"';
            }
            return '<video' . $attributes . '>';
        }, $content);
    }

    protected function renderError($message)
    {
        View::assign([
            'pageSuccess' => false,
            'pageMessage' => strval($message),
            'solutions' => [],
        ]);
        return View::fetch('err_code/index');
    }
}
