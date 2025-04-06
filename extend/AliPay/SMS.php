<?php
namespace ali;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

Class SMS
{
    /**
     * composer 安装
     * composer require alibabacloud/client
     */

    // config 配置信息
    /**
            // 啊里短信配置信息
            'aliNoteInfo' => [
                'accessKeyId' => "",
                'accessSecret' => "",
            ],
     */


    /**
     * 发送短信
     * $receive [
     *      'PhoneNumbers'      接收短信的手机号码。
     *      'SignName'          短信签名名称。
     *      'TemplateCode'      短信模板ID。
     *      'TemplateParam'     短信模板变量对应的实际值，JSON格式。    ['code'=>1111] ( 模板多少参数，对应多少参数 )
     * ]
     * @param $receive
     * @return \AlibabaCloud\Client\Result\Result|string
     * @throws ClientException
     */
    public static function aliNoteSend($receive)
    {

        AlibabaCloud::accessKeyClient($receive['accessKeyId'], $receive['accessSecret'])
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi') // 指定产品
                // ->scheme('https') // https | http
                ->version('2017-05-25') // 指定版本
                ->action('SendSms') // 指定接口
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "default",
                        'PhoneNumbers' => $receive['PhoneNumbers'],
                        'SignName' => $receive['SignName'],
                        'TemplateCode' => $receive['TemplateCode'],
                        'TemplateParam' => json_encode($receive['TemplateParam']),
                    ],
                ])
                ->request();
            return $result;
        } catch (ClientException $e) {
            return $e->getMessage();
        } catch (ServerException $e) {
            return $e->getMessage();
        }
    }

}
