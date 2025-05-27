<?php
/**
 * 邮件发送类
 * 需要先composer安装邮件发送扩展包：composer require phpmailer/phpmailer:*
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/05/02
 * Time: 9:28
 */

namespace app\AppFactory\Kernel\Support\SendNotice;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

define('Host','smtp.qq.com');

/**
 * Class Email
 * @package PHPMailer
 */
class Email
{

    /**
     * 发送邮件
     * @param $params = [
                'host' => 'smtp.qq.com',
                'username' => 'xxxxxxx@qq.com',
                'authCode' => 'asdasdasdsadsa',
                'from' => [
                    'mail' => 'xxx@qq.com',
                    'nickname' => '中山市大可马科技有限公司',
                ],
                'receiver' => [
                    [
                        'mail' => 'xxxx@qq.com',
                        'nickname' => '测试',
                        'replyMail' => 'xxxx@qq.com',
                        'replyNickname' => '中山市大可马科技有限公司',
                    ]
                ],
                'CC' => '',
                'BCC' => '',
                'isHtml' => true,
                'subject' => 'here is subject',
                'body' => '<h1>this is body</h1>',
                'altBody' => 'this is alt body',
            ];
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public static function send($params)
    {
//        include_once EXTEND_PATH . 'PHPMailer/src/PHPMailer.php';
//        include_once EXTEND_PATH . 'PHPMailer/src/SMTP.php';
//        include_once EXTEND_PATH . 'PHPMailer/src/Exception.php';
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = SMTP::DEBUG_OFF;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = $params['host'] ?? Host;                     // SMTP 服务器
        $mail->SMTPAuth   = true;                                   // 开启SMTP授权验证
        $mail->Username   = $params['username'];                     // SMTP 账号
        $mail->Password   = $params['authCode'];                     // SMTP 授权码
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;             // 开启SSL
        $mail->Port       = $params['port'] ?? 465;                  // 端口号

        //Recipients
        $mail->setFrom($params['from']['mail'], $params['from']['nickname']);
        foreach ($params['receiver'] as $key  => $value) {
            $mail->addAddress($value['mail'], $value['nickname']);     // 接收地址邮箱与名称
            $mail->addReplyTo($value['replyMail'], $value['replyNickname']); // 回复指向邮箱地址
        }

        if (isset($params['CC']) && $params['CC']) {
            $mail->addCC($params['CC']);   // 抄送
        }
        if (isset($params['BCC']) && $params['BCC']) {
            $mail->addBCC($params['BCC']);  // 密送
        }

        if (isset($params['attachment']) && $params['attachment']) {
            //Attachments 上传附件，Path：文件路径，Name：文件名
            foreach ($params['attachment'] as $v) {
                $v['path'] = "http://" . $_SERVER['HTTP_HOST'] . $v['path'];
                $mail->addAttachment($v['path'], $v['name']);         //Add attachments
            }
        }

        //Content
        $mail->isHTML($params['isHtml']);                                  //Set email format to HTML
        $mail->Subject = $params['subject'];                               // 标题
        $mail->Body    = $params['body'];                                  // HTML正文
        $mail->AltBody = $params['altBody'] ?? '';                         // 纯文本正文

        return $mail->send();
    }
}