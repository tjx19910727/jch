<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/22
 * Time: 9:43
 */

namespace app\wx\controller;



use app\AppFactory\AppFactory;
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
}
