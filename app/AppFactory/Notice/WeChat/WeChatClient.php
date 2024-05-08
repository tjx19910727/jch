<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/2
 * Time: 9:34
 */

namespace app\AppFactory\Notice\WeChat;


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
        // 发送类型为微信
        if ($this->config['sendType'] == 1 &&
            isset($this->config['config']) && $this->config['config'] &&
            isset($this->config['template']) && $this->config['template'] &&
            isset($this->config['receiver']) && $this->config['receiver']) {
            try {
                $app = Factory::officialAccount($this->config['config']);
                foreach ($this->config['receiver'] as $key => $value) {
                    $data = [
                        "touser" => $value['openid'],
                        "template_id" => $this->config['template']['template_id'],
                    ];
                    if ($this->config['template']['url']) $data['url'] = $this->config['template']['url'];
                    if ($this->config['template']['miniprogram']) $data['miniprogram'] = json2arr($this->config['template']['miniprogram']);
                    $body = json2arr($this->config['template']['body']);
                    foreach ($body as $bk => $bv) {
                        $data['data'][$bv['field']] = $bv['value'];
                    }
                    $result = $app->template_message->send($data);
                    actionLog($result,'发送微信通知结果');
                    $this->addTemplateLog($value,$data,$result);
                    return $result;
                }
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
    }

    private function addTemplateLog($receiver,$data,$result)
    {
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
            "remark" => json_encode($result),
            "status" => 1,
            "ao_id" => $this->config['config']['ao_id'],
        ];
        return $this->addWxTemplateLog($insert);
    }
}