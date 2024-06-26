<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/25
 * Time: 17:32
 */

namespace app\AppFactory\Kernel\Traits\Send;


use app\AppFactory\AppFactory;

trait ToManagerTrait
{
    /**
     * @var array 消息通知数据
    [
        "ao_id" => 6,
        "templateType" => "online",
        "replaceData" => [
            "online" => "在线",
            "machine_id" => "test0003",
            "machine_name" => "测试3号机",
        ],
    ]
     */
    public $noticeSendData;

    public function noticeSend()
    {
        $app = AppFactory::notice($this->noticeSendData);
        $result = $app->send();
        return $result;
    }
}