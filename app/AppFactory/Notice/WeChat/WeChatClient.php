<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/2
 * Time: 9:34
 */

namespace app\AppFactory\Notice\WeChat;


use app\AppFactory\Kernel\Support\FaultNotice\FaultWechatTemplate;
use app\AppFactory\Notice\NoticeBaseClient;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use GuzzleHttp\Exception\GuzzleException;

class WeChatClient extends NoticeBaseClient
{
    /**
     * 发送消息
     * @return string
     */
    public function send()
    {
        actionLog($this->config,'发送微信消息模板总数据');
        // 发送类型为微信
        if ($this->config['sendType'] == 1 &&
            isset($this->config['config']) && $this->config['config'] &&
            isset($this->config['template']) && $this->config['template'] &&
            isset($this->config['receiver']) && $this->config['receiver']) {
            try {
                $app = Factory::officialAccount($this->config['config']);
                foreach ($this->config['receiver'] as $key => $value) {
                    if ($value['openid']) {
                        $data = [
                            "touser" => $value['openid'],
                            "template_id" => $this->config['template']['template_id'],
                        ];
                        if ($this->config['template']['url']) $data['url'] = $this->config['template']['url'];
                        if ($this->config['template']['miniprogram']) $data['miniprogram'] = json2arr($this->config['template']['miniprogram']);
                        $body = json2arr($this->config['template']['body']);
                        foreach ($body as $bk => $bv) {
                            foreach ($bv as $bvk => $bvv) {
                                $data['data'][$bvv['field']] = trim($bvv['value']);
                            }
                        }

                        $wtlId = $this->addTemplateLog($value, $data);
                        if ($wtlId > 0 && isset($this->config['templateType']) && $this->config['templateType'] === 'mFault') {
                            $confirmUrl = $this->buildConfirmUrl($wtlId);
                            if ($confirmUrl) {
                                $data['url'] = $confirmUrl;
                            }
                        }

                        actionLog($data, '发送微信通知数据');
                        $result = @$app->template_message->send($data);
                        actionLog($result, '发送微信通知结果');
                        $sendStatus = (is_array($result) && isset($result['errcode']) && intval($result['errcode']) === 0) ? 1 : 2;
                        $this->updateTemplateLogResult($wtlId, $result, $sendStatus);
                    }
                }
                return true;
            } catch (InvalidArgumentException $e) {
                actionException($e,1);
                return $e->getMessage();
            } catch (InvalidConfigException $e) {
                actionException($e,1);
                return $e->getMessage();
            } catch (GuzzleException $e) {
                actionException($e,1);
                return $e->getMessage();
            }
        }
        return true;
    }

    /**
     * 新故障流程发送消息。
     *
     * 与send()隔离，避免固定故障模板、事件日志字段和跳转规则影响历史通知流程。
     * @return bool|string
     */
    public function sendV2()
    {
        actionLog($this->config, '发送V2微信故障消息模板总数据');
        if ($this->config['sendType'] == 1 &&
            isset($this->config['config']) && $this->config['config'] &&
            isset($this->config['template']) && $this->config['template'] &&
            isset($this->config['receiver']) && $this->config['receiver']) {
            try {
                $app = Factory::officialAccount($this->config['config']);
                foreach ($this->config['receiver'] as $value) {
                    if ($value['openid']) {
                        $data = [
                            'touser' => $value['openid'],
                            'template_id' => $this->config['template']['template_id'],
                        ];
                        if ($this->config['template']['url']) $data['url'] = $this->config['template']['url'];
                        if ($this->config['template']['miniprogram']) $data['miniprogram'] = json2arr($this->config['template']['miniprogram']);
                        $body = json2arr($this->config['template']['body']);
                        foreach ($body as $bv) {
                            foreach ($bv as $bvv) {
                                $data['data'][$bvv['field']] = trim($bvv['value']);
                            }
                        }

                        $wtlId = $this->addTemplateLogV2($value, $data);
                        $templateType = strval($this->config['templateType'] ?? '');
                        if ($wtlId > 0 && in_array($templateType, FaultWechatTemplate::types(), true)) {
                            $confirmUrl = $this->buildConfirmUrl($wtlId);
                            if ($confirmUrl) {
                                $data['url'] = $confirmUrl;
                            }
                        }

                        actionLog($data, '发送V2微信故障通知数据');
                        $result = @$app->template_message->send($data);
                        actionLog($result, '发送V2微信故障通知结果');
                        $sendStatus = (is_array($result) && isset($result['errcode']) && intval($result['errcode']) === 0) ? 1 : 2;
                        $this->updateTemplateLogResult($wtlId, $result, $sendStatus);
                    }
                }
                return true;
            } catch (InvalidArgumentException $e) {
                actionException($e, 1);
                return $e->getMessage();
            } catch (InvalidConfigException $e) {
                actionException($e, 1);
                return $e->getMessage();
            } catch (GuzzleException $e) {
                actionException($e, 1);
                return $e->getMessage();
            }
        }
        return true;
    }

    private function addTemplateLog($receiver, $data)
    {
        $errorCode = strval($this->config['replaceData']['error_info'] ?? $this->config['replaceData']['errorCode'] ?? '');
        $insert = [
            "wt_id" => $this->config['template']['wt_id'],
            "template_name" => $this->config['template']['template_name'],
            "wx_id" => $this->config['template']['wx_id'],
            "manager_id" => $receiver['manager_id'],
            "nickname" => $receiver['nickname'],
            "openid" => $receiver['openid'],
            "template_id" => $this->config['template']['template_id'],
            "params" => json_encode($data['data']),
            "template_type" => $this->config['template']['template_type'],
            "remark" => '',
            "status" => 1,
            "ao_id" => $this->config['ao_id'] ?? 1,
            "me_id" => intval($this->config['me_id'] ?? 0),
            "m_id" => intval($this->config['m_id'] ?? 0),
            "error_code" => $errorCode,
            "send_status" => 2,
            "confirm_status" => 2,
            "confirm_time" => 0,
        ];
        return $this->addWxTemplateLog($insert);
    }

    /**
     * 新故障流程通知日志。
     *
     * error_code优先读取新流程显式传入的原始故障码，避免被模板展示字段复用关系影响。
     */
    private function addTemplateLogV2($receiver, $data)
    {
        $errorCode = strval(
            $this->config['error_code'] ??
            $this->config['replaceData']['error_info'] ??
            $this->config['replaceData']['errorCode'] ??
            ''
        );
        $insert = [
            'wt_id' => $this->config['template']['wt_id'],
            'template_name' => $this->config['template']['template_name'],
            'wx_id' => $this->config['template']['wx_id'],
            'manager_id' => $receiver['manager_id'],
            'nickname' => $receiver['nickname'],
            'openid' => $receiver['openid'],
            'template_id' => $this->config['template']['template_id'],
            'params' => json_encode($data['data']),
            'template_type' => $this->config['template']['template_type'],
            'remark' => '',
            'status' => 1,
            'ao_id' => $this->config['ao_id'] ?? 1,
            'me_id' => intval($this->config['me_id'] ?? 0),
            'm_id' => intval($this->config['m_id'] ?? 0),
            'error_code' => $errorCode,
            'send_status' => 2,
            'confirm_status' => 2,
            'confirm_time' => 0,
        ];
        return $this->addWxTemplateLog($insert);
    }

    private function updateTemplateLogResult($wtlId, $result, $sendStatus)
    {
        if (!$wtlId) {
            return;
        }
        $remark = $sendStatus == 1 ? '发送成功' : ('发送失败：' .(isset($result['errcode']) ? ($result['errcode'].'-') : ''). ($result['errmsg'] ?? '未知错误'));
        $this->updateWxTemplateLog([
            'remark' => $remark,
            'send_status' => $sendStatus,
        ],['wtl_id' => $wtlId]);
    }

    private function buildConfirmUrl($wtlId)
    {
        $host = rtrim(config('app.app_host') ?: env('app.host', ''), '/');
        if (!$host) {
            actionLog(['wtl_id' => $wtlId], '未配置app_host，无法生成确认链接', 'noticeSend');
            return '';
        }

        $expire = strtotime(date('Y-m-d 23:59:59'));
        $secret = config('app.salt') ?: 'startup_notice_secret';
        $sign = hash('sha256', intval($wtlId) . '|' . intval($expire) . '|' . $secret);
        $query = http_build_query([
            'wtl_id' => intval($wtlId),
            'expire' => intval($expire),
            'sign' => $sign,
        ]);
        $errorCode = strval($this->config['replaceData']['error_info'] ?? '');
        $path = $errorCode === '12202011'
            ? '/wx/official/shutdownNotice'
            : '/wx/official/confirmStartupNotice';
        return $host . $path . '?' . $query;
    }
}
