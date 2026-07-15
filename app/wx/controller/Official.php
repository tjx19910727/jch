<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/22
 * Time: 9:43
 */

namespace app\wx\controller;



use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Machine\MachineShutdownCommandModel;
use app\BaseController;
use app\AppFactory\Kernel\Traits\ReturnTrait;
use think\facade\Db;

class Official extends BaseController
{
    use ReturnTrait;

    // 接收微信公众号通知
    public function receive()
    {
        $data = input();
        if ($data)
            actionLog($data, '接收到的数据');
        if (isset($data['echostr'])) {
            die($data['echostr']);
        }
        $xml = file_get_contents("php://input");
        actionLog($xml, "xml");
        $message = FromXml($xml);
        $message = json_decode(json_encode($message), true);
        actionLog($message,'XML转格式');
        AppFactory::wx()->official->receiveHandle($message);
    }

    // 公众号菜单栏获取
    public function getMenu(){
        $data = input(); 
        if (empty($data['gh_id'])) {
            return $this->rFail('未传入公众号原始ID');
        }
        return AppFactory::wx()->official->menuList($data);
    }

    // 公众号菜单栏修改
    public function editMenu(){
        $data = input();
        actionLog($data, '修改菜单数据');
        if (empty($data['gh_id'])) return $this->rFail('未传入原始id');
        if (empty($data['menu']))  return $this->rFail('未正确传入菜单数据');
        return AppFactory::wx()->official->editMenu($data);
    }

    // 点击模板消息链接后确认：更新通知确认状态
    public function confirmStartupNotice()
    {
        $renderHtml = function ($message, $success = false) {
            $title = $success ? '确认成功' : '确认失败';
                        $accent = $success ? '#07C160' : '#ED2633';
                        $badgeBg = $success ? 'rgba(15,138,95,0.12)' : 'rgba(237,38,51,0.12)';
                        $badgeText = $success ? 'SUCCESS' : 'FAILED';
                        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

                        return "<!doctype html>
<html lang=\"zh-CN\">
<head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">
    <title>{$safeTitle}</title>
    <style>
        :root { color-scheme: light; }
        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', 'Noto Sans CJK SC', 'Segoe UI', sans-serif;
            background:
                radial-gradient(circle at 10% 10%, #f9f4ea 0, rgba(249,244,234,0) 45%),
                radial-gradient(circle at 90% 20%, #eaf5ff 0, rgba(234,245,255,0) 42%),
                linear-gradient(160deg, #f4f7fb 0%, #eef3f9 45%, #f7fafc 100%);
            color: #1f2a37;
        }
        .panel {
            width: min(760px, 100%);
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(255,255,255,0.65);
            border-radius: 22px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14);
            backdrop-filter: blur(5px);
            overflow: hidden;
        }
        .top {
            padding: 26px 30px 18px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand {
            font-size: 14px;
            letter-spacing: 0.08em;
            color: #6b7280;
            text-transform: uppercase;
        }
        .badge {
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            color: {$accent};
            background: {$badgeBg};
        }
        .content {
            padding: 28px 30px 34px;
            text-align: center;
        }
        .title {
            margin: 0;
            font-size: 18px;
            line-height: 1.28;
            color: {$accent};
            font-weight: 800;
            letter-spacing: 0.02em;
        }
        .desc {
            margin: 18px auto 0;
            max-width: 560px;
            font-size: 14px;
            line-height: 1.9;
            color: #344256;
            font-weight: 500;
        }
        .footer {
            margin-top: 22px;
            font-size: 12px;
            color: #8a95a6;
            letter-spacing: 0.02em;
        }
        @media (max-width: 640px) {
            body { padding: 14px; }
            .top, .content { padding-left: 18px; padding-right: 18px; }
            .top { padding-top: 18px; padding-bottom: 12px; }
            .content { padding-top: 20px; padding-bottom: 24px; }
            .title {
                font-size: clamp(28px, 9vw, 66px);
                line-height: 1.06;
                letter-spacing: 0.01em;
                font-weight: 800;
                word-break: break-word;
            }
            .desc { font-size: 16px; line-height: 1.75; }
            .footer { font-size: 13px; }
            .brand { font-size: 13px; }
            .badge { font-size: 13px; }
        }
    </style>
</head>
<body>
    <div class=\"panel\">
        <div class=\"top\">
            <div class=\"brand\">Notice Center</div>
            <div class=\"badge\">{$badgeText}</div>
        </div>
        <div class=\"content\">
            <h1 class=\"title\">{$safeTitle}</h1>
            <p class=\"desc\">{$safeMessage}</p>
            <div class=\"footer\">消息确认结果已同步到系统</div>
        </div>
    </div>
</body>
</html>";
        };

        $now = time();
        $wtlId = intval(input('wtl_id'));
        $expire = intval(input('expire'));
        $sign = trim((string)input('sign'));

        if (!$wtlId || !$expire || !$sign) {
            return $renderHtml('参数错误，请返回公众号重试。');
        }
        if ($expire < $now) {
            return $renderHtml('该确认链接已失效，请以最新消息为准。');
        }

        $secret = config('app.salt') ?: 'startup_notice_secret';
        $localSign = hash('sha256', $wtlId . '|' . $expire . '|' . $secret);
        if (!hash_equals($localSign, $sign)) {
            return $renderHtml('签名校验失败，请返回公众号重试。');
        }

        $log = Db::name('wx_template_log')->where('wtl_id', $wtlId)->find();
        if (!$log) {
            return $renderHtml('通知记录不存在，请返回公众号查看最新消息。');
        }

        if (intval($log['send_status'] ?? 0) !== 1) {
            return $renderHtml('该通知尚未发送成功，暂不可确认。');
        }

        if (intval($log['confirm_status'] ?? 0) === 1) {
            return $renderHtml('该通知已确认，无需重复操作。', true);
        }

        Db::name('wx_template_log')->where('wtl_id', $wtlId)->update([
            'confirm_status' => 1,
            'confirm_time' => $now,
        ]);

        actionLog([
            'wtl_id' => $wtlId,
            'expire' => $expire,
        ], '公众号点击确认通知', 'confirmStartupNotice');

        return $renderHtml('已确认收到该通知。', true);
    }

    /**
     * 设备超时未关机通知操作页及关机指令提交。
     * GET 展示确认页，POST 再次校验全部条件后下发关机指令。
     */
    public function shutdownNotice()
    {
        $now = time();
        $wtlId = intval(input('wtl_id'));
        $expire = intval(input('expire'));
        $sign = trim((string)input('sign'));

        $check = $this->validateShutdownNotice($wtlId, $expire, $sign, $now);
        if (!$check['success']) {
            return $this->renderShutdownNotice($check['message']);
        }

        $log = $check['log'];
        $machine = $check['machine'];
        $shutdownTimestamp = intval($check['shutdown_timestamp']);
        $incidentKey = date('Ymd', $shutdownTimestamp);
        $commandWhere = [
            'openid' => strval($log['openid']),
            'm_id' => intval($machine['m_id']),
            'incident_key' => $incidentKey,
        ];

        $successfulCommand = MachineShutdownCommandModel::where($commandWhere)
            ->where('status', 2)
            ->find();
        if ($successfulCommand) {
            return $this->renderShutdownNotice('您已成功下发过本次关机指令，请勿重复操作。');
        }

        if (!request()->isPost()) {
            $message = '设备：' . $machine['machine_name'] . '（' . $machine['machine_id'] . '）'
                . "\n计划关机时间：" . date('Y-m-d H:i:s', $shutdownTimestamp)
                . "\n设备当前仍在线，是否立即发送关机指令？";
            return $this->renderShutdownNotice($message, true);
        }

        try {
            $reservation = Db::transaction(function () use ($commandWhere, $log, $machine, $shutdownTimestamp, $incidentKey, $now) {
                $command = MachineShutdownCommandModel::where($commandWhere)->lock(true)->find();
                if ($command) {
                    if (intval($command['status']) === 2) {
                        return ['success' => false, 'message' => '您已成功下发过本次关机指令，请勿重复操作。'];
                    }
                    if (intval($command['status']) === 1) {
                        return ['success' => false, 'message' => '关机指令正在处理中，请勿重复提交。'];
                    }
                    $command->save([
                        'wtl_id' => intval($log['wtl_id']),
                        'me_id' => intval($log['me_id'] ?? 0),
                        'status' => 1,
                        'result' => '',
                        'command_time' => 0,
                        'update_time' => $now,
                    ]);
                    return ['success' => true, 'msc_id' => intval($command['msc_id'])];
                }

                $command = MachineShutdownCommandModel::create([
                    'wtl_id' => intval($log['wtl_id']),
                    'me_id' => intval($log['me_id'] ?? 0),
                    'manager_id' => intval($log['manager_id']),
                    'openid' => strval($log['openid'] ?? ''),
                    'm_id' => intval($machine['m_id']),
                    'machine_id' => strval($machine['machine_id']),
                    'incident_key' => $incidentKey,
                    'shutdown_time' => $shutdownTimestamp,
                    'status' => 1,
                    'result' => '',
                    'command_time' => 0,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
                return ['success' => true, 'msc_id' => intval($command['msc_id'])];
            });
        } catch (\Throwable $e) {
            actionException($e, 1, 'shutdownNotice');
            return $this->renderShutdownNotice('关机指令正在处理或已提交，请勿重复操作。');
        }

        if (!$reservation['success']) {
            return $this->renderShutdownNotice($reservation['message']);
        }

        $mscId = intval($reservation['msc_id']);
        try {
            $key = strval($machine['signKey'] ?? '');
            if (!$key) {
                $key = strval(env('api.md5Key'));
            }
            if (!$key) {
                throw new \RuntimeException('设备签名密钥不存在，无法发送关机指令。');
            }

            $app = AppFactory::machine([
                'machine_id' => $machine['machine_id'],
                'key' => $key,
                'mac' => $machine['mac_address'] ?? '',
            ]);
            $result = $app->sendMq->sendMq('shutdown');
            $resultData = obj2arr($result);
            $success = is_array($resultData) && intval($resultData['state'] ?? 0) === 200;
            $resultText = json_encode($resultData, JSON_UNESCAPED_UNICODE);

            MachineShutdownCommandModel::where('msc_id', $mscId)->update([
                'status' => $success ? 2 : 3,
                'result' => $resultText,
                'command_time' => $success ? time() : 0,
                'update_time' => time(),
            ]);

            if (!$success) {
                return $this->renderShutdownNotice('关机指令下发失败：' . strval($resultData['msg'] ?? '未知错误'));
            }

            Db::name('wx_template_log')->where('wtl_id', $wtlId)->update([
                'confirm_status' => 1,
                'confirm_time' => time(),
            ]);
            actionLog([
                'msc_id' => $mscId,
                'wtl_id' => $wtlId,
                'manager_id' => intval($log['manager_id']),
                'm_id' => intval($machine['m_id']),
                'machine_id' => $machine['machine_id'],
                'shutdown_time' => $shutdownTimestamp,
            ], '公众号确认页下发设备关机指令', 'shutdownNotice');

            return $this->renderShutdownNotice('关机指令已成功下发，请勿重复操作。', false, true);
        } catch (\Throwable $e) {
            MachineShutdownCommandModel::where('msc_id', $mscId)->update([
                'status' => 3,
                'result' => $e->getMessage(),
                'update_time' => time(),
            ]);
            actionException($e, 1, 'shutdownNotice');
            return $this->renderShutdownNotice('关机指令下发失败：' . $e->getMessage());
        }
    }

    private function validateShutdownNotice($wtlId, $expire, $sign, $now)
    {
        if (!$wtlId || !$expire || !$sign) {
            return ['success' => false, 'message' => '参数错误，请返回公众号重试。'];
        }
        if ($expire < $now) {
            return ['success' => false, 'message' => '该关机链接已失效，无法发送关机指令。'];
        }

        $secret = config('app.salt') ?: 'startup_notice_secret';
        $localSign = hash('sha256', $wtlId . '|' . $expire . '|' . $secret);
        if (!hash_equals($localSign, $sign)) {
            return ['success' => false, 'message' => '签名校验失败，请返回公众号重试。'];
        }

        $log = Db::name('wx_template_log')->where('wtl_id', $wtlId)->find();
        if (!$log) {
            return ['success' => false, 'message' => '通知记录不存在，请返回公众号查看最新消息。'];
        }
        if (intval($log['send_status'] ?? 0) !== 1) {
            return ['success' => false, 'message' => '该通知尚未发送成功，无法发送关机指令。'];
        }
        if (strval($log['error_code'] ?? '') !== '12202011') {
            return ['success' => false, 'message' => '该通知不是设备超时未关机通知，禁止执行关机操作。'];
        }
        if (intval($log['manager_id'] ?? 0) <= 0 || strval($log['openid'] ?? '') === '') {
            return ['success' => false, 'message' => '通知接收人信息不完整，无法确认关机操作用户。'];
        }

        $machine = Db::name('machine')
            ->where('m_id', intval($log['m_id']))
            ->field('m_id,machine_id,machine_name,online,http_online,is_operating,mac_address,signKey')
            ->find();
        if (!$machine) {
            return ['success' => false, 'message' => '设备不存在，无法发送关机指令。'];
        }
        if (intval($machine['is_operating']) !== 1) {
            return ['success' => false, 'message' => '设备已不处于运营状态，无法发送关机指令。'];
        }
        // 关机指令通过设备 MQ 通道下发，必须以 machine.online 为准。
        if (intval($machine['online']) !== 1) {
            return ['success' => false, 'message' => '设备当前不在线，无需或无法发送关机指令。'];
        }

        $onOff = Db::name('machine_on_off')
            ->where('m_id', intval($machine['m_id']))
            ->where('status', 1)
            ->whereNotNull('on_off_machine')
            ->where('on_off_machine', '<>', '')
            ->where('on_off_machine', '<>', '{}')
            ->order('moo_id desc')
            ->find();
        if (!$onOff) {
            return ['success' => false, 'message' => '设备没有有效的定时开关机配置，无法确认是否超时未关机。'];
        }

        $shutdownTimestamp = $this->getOverdueShutdownTimestamp($onOff['on_off_machine'], $now);
        if (!$shutdownTimestamp) {
            return ['success' => false, 'message' => '设备当前不符合“超过计划关机时间30分钟仍在线”的条件，禁止发送关机指令。'];
        }

        return [
            'success' => true,
            'log' => $log,
            'machine' => $machine,
            'shutdown_timestamp' => $shutdownTimestamp,
        ];
    }

    /**
     * 与 checkOperatingShutdown 相同的超时关机时间判断。
     */
    private function getOverdueShutdownTimestamp($onOffMachine, $now)
    {
        if (is_string($onOffMachine)) {
            $onOffMachine = json_decode($onOffMachine, true);
        }
        if (!is_array($onOffMachine)) {
            return 0;
        }

        $today = strtotime(date('Y-m-d', $now));
        $yesterday = strtotime('-1 day', $today);
        $todayWeekKey = (string)(intval(date('N', $today)) - 1);
        $yesterdayWeekKey = (string)(intval(date('N', $yesterday)) - 1);
        $nowSec = $now - $today;
        $lateShutdownSec = 23 * 3600 + 30 * 60;

        $parseOnOff = function ($weekKey) use ($onOffMachine) {
            if (!array_key_exists($weekKey, $onOffMachine)) {
                return null;
            }
            $onOffTime = explode(',', strval($onOffMachine[$weekKey]));
            $shutdownTime = trim($onOffTime[0] ?? '');
            $startupTime = trim($onOffTime[1] ?? '');
            if (
                $shutdownTime === '' || $startupTime === '' ||
                strtolower($shutdownTime) === 'null' || strtolower($startupTime) === 'null' ||
                strtotime($shutdownTime) === false || strtotime($startupTime) === false
            ) {
                return null;
            }
            $shutdownSec = HourMinuteSec2int($shutdownTime);
            $startupSec = HourMinuteSec2int($startupTime);
            return [
                'shutdown_sec' => $shutdownSec,
                'startup_sec' => $startupSec,
                'is_cross_day' => $shutdownSec <= $startupSec,
            ];
        };

        $todayOnOff = $parseOnOff($todayWeekKey);
        $yesterdayOnOff = $parseOnOff($yesterdayWeekKey);
        if (
            $yesterdayOnOff && $yesterdayOnOff['is_cross_day'] &&
            $nowSec <= $yesterdayOnOff['shutdown_sec']
        ) {
            return 0;
        }
        if ($todayOnOff && $nowSec >= $todayOnOff['startup_sec']) {
            if ($todayOnOff['is_cross_day'] || $nowSec <= $todayOnOff['shutdown_sec']) {
                return 0;
            }
        }

        $shutdownTimestamp = 0;
        $beforeTodayStartup = !$todayOnOff || $nowSec < $todayOnOff['startup_sec'];
        if ($yesterdayOnOff && $beforeTodayStartup) {
            if ($yesterdayOnOff['is_cross_day']) {
                $shutdownTimestamp = $today + $yesterdayOnOff['shutdown_sec'];
            } elseif ($yesterdayOnOff['shutdown_sec'] >= $lateShutdownSec) {
                $shutdownTimestamp = $yesterday + $yesterdayOnOff['shutdown_sec'];
            }
        }
        if (
            $todayOnOff && !$todayOnOff['is_cross_day'] &&
            $todayOnOff['shutdown_sec'] < $lateShutdownSec &&
            $nowSec > $todayOnOff['shutdown_sec']
        ) {
            $shutdownTimestamp = $today + $todayOnOff['shutdown_sec'];
        }

        return $shutdownTimestamp && $now > $shutdownTimestamp + 1800 ? $shutdownTimestamp : 0;
    }

    private function renderShutdownNotice($message, $showButton = false, $success = false)
    {
        $title = $success ? '下发成功' : ($showButton ? '确认关机' : '操作失败');
        $accent = $success ? '#07C160' : ($showButton ? '#d97706' : '#ED2633');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $button = '';
        if ($showButton) {
            $button = '<form method="post"><button type="submit" onclick="return confirm(\'确认向该设备发送关机指令吗？此操作不可重复。\');">确定关机</button></form>';
        }

        return "<!doctype html>
<html lang=\"zh-CN\">
<head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">
    <title>{$safeTitle}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:#f4f7fb;font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Microsoft YaHei',sans-serif;color:#1f2937}.panel{width:min(620px,100%);padding:30px 24px;background:#fff;border-radius:20px;box-shadow:0 20px 55px rgba(15,23,42,.13);text-align:center}.title{margin:0;color:{$accent};font-size:28px}.desc{margin:20px 0 0;line-height:1.9;font-size:16px;color:#475569}form{margin-top:26px}button{width:100%;max-width:320px;border:0;border-radius:12px;padding:15px 20px;background:#dc2626;color:#fff;font-size:17px;font-weight:700;cursor:pointer}button:active{background:#b91c1c}.footer{margin-top:22px;font-size:12px;color:#94a3b8}
    </style>
</head>
<body><div class=\"panel\"><h1 class=\"title\">{$safeTitle}</h1><div class=\"desc\">{$safeMessage}</div>{$button}<div class=\"footer\">设备远程操作中心</div></div></body>
</html>";
    }
}
