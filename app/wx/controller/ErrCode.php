<?php

namespace app\wx\controller;

use EasyWeChat\Factory;
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
    const SESSION_PREFIX = 'wx_fault_detail_authorized_';

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
                return $this->renderError('无权限查看该故障详情');
            }

            Session::set($this->getSessionKey(intval($log['wtl_id'])), $openid);
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

        $level = intval($event['level'] ?? 0);
        $levelClassMap = [1 => 'urgent', 2 => 'normal', 3 => 'notice'];
        View::assign([
            'pageSuccess' => true,
            'pageMessage' => '',
            'machineName' => strval($event['machine_name'] ?? '') ?: '--',
            'machineId' => strval($event['machine_id'] ?? '') ?: '--',
            'address' => strval($event['address'] ?? '') ?: '--',
            'categoryName' => strval($event['category_name'] ?? '') ?: '设备故障',
            'errorCode' => $errorCode ?: '--',
            'levelName' => strval($event['level_name'] ?? '') ?: $this->getDefaultLevelName($level),
            'levelClass' => $levelClassMap[$level] ?? 'normal',
            'occurTime' => intval($event['create_time'] ?? 0) > 0
                ? date('Y-m-d H:i:s', intval($event['create_time']))
                : '--',
            'description' => trim(strval($event['msg'] ?? ''))
                ?: (trim(strval($event['remark'] ?? '')) ?: strval($event['error_name'] ?? '')),
            'solutions' => $solutions,
        ]);
        return View::fetch('err_code/index');
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
                'mecnr.ao_id = mec.ao_id AND mecnr.error_code = mec.errorCode'
            )
            ->leftJoin(
                'machine_fault_category mfc',
                'mfc.ao_id = mec.ao_id AND mfc.category_id = mec.category_id'
            )
            ->leftJoin('machine_fault_level mfl', 'mfl.level = mec.level')
            ->where('mec.me_id', $meId)
            ->where('mec.m_id', intval($log['m_id'] ?? 0))
            ->where('mec.ao_id', intval($log['ao_id'] ?? 0))
            ->where('mec.errorCode', strval($log['error_code'] ?? ''))
            ->field(
                'mec.me_id,mec.machine_id,mec.machine_name,mec.address,mec.errorCode AS error_code,' .
                'mec.level,mec.remark,mec.msg,mec.create_time,' .
                "COALESCE(NULLIF(mecnr.error_name,''),NULLIF(mec.remark,''),mec.errorCode) AS error_name," .
                'mfc.category_name,mfl.level_name'
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

    protected function getDefaultLevelName($level)
    {
        return [1 => '紧急', 2 => '一般', 3 => '提示'][$level] ?? '--';
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
