<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/2
 * Time: 9:32
 */

namespace app\AppFactory\Notice\Email;


use app\AppFactory\Kernel\Traits\Email\EmailTemplateLogTrait;
use app\AppFactory\Notice\NoticeBaseClient;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class EmailClient extends NoticeBaseClient
{
    use EmailTemplateLogTrait;

    /**
     * 发送邮件
     */
    public function send()
    {
        // 发送类型为邮件，配置信息、模板信息、收件人信息不能为空
        if ($this->config['sendType'] == 2 &&
            isset($this->config['config']) && $this->config['config'] &&
            isset($this->config['template']) && $this->config['template'] &&
            isset($this->config['receiver']) && $this->config['receiver']) {
            $result = $this->sendTemplate();
            actionLog($result,'发送邮件结果');
            if ($result) {
                $this->addTemplateLog($result);
            }
        }
    }

    /**
     * 生成消息通知日志记录
     * @param $result
     */
    public function addTemplateLog($result)
    {
        foreach ($this->config['receiver'] as $k => $v) {
            $insert = [
                "send_id" => $this->config['config']['creator'],
                "send_email" => $this->config['config']['sendEmail'],
                "send_name" => $this->config['config']['nickname'],
                "reply_name" => $this->config['config']['replyNickname'],
                "reply_email" => $this->config['config']['replyMail'],
                "CC" => $this->config['template']['CC'],
                "BCC" => $this->config['template']['BCC'],
                "receiver_id" => $v['manager_id'],
                "receiver" => $v['nickname'],
                "receive_email" => $v['email'],
                "subject" => $this->config['template']['subject'],
                "body" => $this->config['template']['body'],
                "altBody" => $this->config['template']['altBody'],
                "attachment" => $this->config['template']['attachment'],
                "ec_id" => $this->config['template']['ec_id'],
                "et_id" => $this->config['template']['et_id'],
                "template_type" => $this->config['template']['template_type'],
                "sendResult" => $result,
                "ao_id" => $v['ao_id'],
            ];
            $flag[] = $this->addEmailTemplateLog($insert);
        }
        actionLog($flag,'生成日志结果');
    }

    /**
     * 发送邮箱消息模板
     * @return bool|string
     */
    private function sendTemplate()
    {
        try {
            $config = $this->config['config'];
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = SMTP::DEBUG_OFF;//Enable verbose debug output
            $mail->isSMTP();//Send using SMTP
            $mail->Host = $config['host'] ?? Host;// SMTP 服务器
            $mail->SMTPAuth = true;// 开启SMTP授权验证
            $mail->Username = $config['username'];// SMTP 账号
            $mail->Password = $config['authCode'];// SMTP 授权码
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;// 开启SSL
            $mail->Port = 465;// 端口号
            //Recipients
            $mail->setFrom($config['sendEmail'], $config['nickname']);
            $mail->isHTML($config['isHtml']);//Set email format to HTML
            foreach ($this->config['receiver'] as $key => $value) {
                $mail->addAddress($value['email'], $value['nickname']);     // 接收地址邮箱与名称
                $mail->addReplyTo($config['replyMail'], $config['replyNickname']); // 回复指向邮箱地址
            }
            if (isset($this->config['template']['CC']) && $this->config['template']['CC']) {
                $mail->addCC($this->config['template']['CC']);   // 抄送
            }
            if (isset($this->config['template']['BCC']) && $this->config['template']['BCC']) {
                $mail->addBCC($this->config['template']['BCC']);  // 密送
            }
            if (isset($this->config['template']['attachment']) && $this->config['template']['attachment']) {
                $this->config['template']['attachment'] = json2arr($this->config['template']['attachment']);
                //Attachments 上传附件，Path：文件路径，Name：文件名
                foreach ($this->config['template']['attachment'] as $v) {
                    $v['path'] = "http://" . $_SERVER['HTTP_HOST'] . $v['path'];
                    $mail->addAttachment($v['path'], $v['name']);         //Add attachments
                }
            }//Content
            $mail->Subject = $this->config['template']['subject'];// 标题
            $mail->Body = $this->config['template']['body'];// HTML正文
            $mail->AltBody = $this->config['template']['altBody'] ?? '';// 纯文本正文
            return $mail->send();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}