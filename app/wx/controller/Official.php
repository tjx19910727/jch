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
use think\facade\Cache;

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

    // 点击模板消息链接后确认：今日不再发送设备未开机提醒
    public function confirmStartupNotice()
    {
        $renderHtml = function ($message, $success = false) {
            $title = $success ? '确认成功' : '确认失败';
            $color = $success ? '#0a7f3f' : '#c62828';
            return "<!doctype html><html><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>{$title}</title></head><body style=\"margin:0;background:#f5f7fb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;\"><div style=\"max-width:640px;margin:48px auto;padding:0 16px;\"><div style=\"background:#fff;border-radius:14px;padding:28px 20px;box-shadow:0 8px 28px rgba(0,0,0,.06);text-align:center;\"><h2 style=\"margin:0 0 12px;color:{$color};font-size:22px;\">{$title}</h2><p style=\"margin:0;color:#334155;line-height:1.7;font-size:16px;\">{$message}</p></div></div></body></html>";
        };

        $now = time();
        $mId = intval(input('m_id'));
        $dayKey = trim((string)input('d'));
        $expire = intval(input('expire'));
        $sign = trim((string)input('sign'));

        if (!$mId || !$dayKey || !$expire || !$sign) {
            return $renderHtml('参数错误，请返回公众号重试。');
        }
        if (!preg_match('/^\d{8}$/', $dayKey)) {
            return $renderHtml('参数格式错误，请返回公众号重试。');
        }
        if ($dayKey !== date('Ymd', $now)) {
            return $renderHtml('该确认链接已过期，请以最新消息为准。');
        }
        if ($expire < $now) {
            return $renderHtml('该确认链接已失效，请以最新消息为准。');
        }

        $secret = config('app.salt') ?: 'startup_notice_secret';
        $localSign = hash('sha256', $mId . '|' . $dayKey . '|' . $expire . '|' . $secret);
        if (!hash_equals($localSign, $sign)) {
            return $renderHtml('签名校验失败，请返回公众号重试。');
        }

        $ttl = strtotime(date('Y-m-d 23:59:59', $now)) - $now;
        Cache::set('machine_startup_exception_mute:' . $mId . ':' . $dayKey, 1, $ttl > 0 ? $ttl : 60);
        actionLog([
            'm_id' => $mId,
            'day' => $dayKey,
            'expire' => $expire,
        ], '公众号点击确认今日不再提醒', 'confirmStartupNotice');

        return $renderHtml('已确认，今日将不再发送该设备未开机提醒。', true);
    }
}
