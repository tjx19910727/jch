<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/22
 * Time: 9:16
 */

namespace app\api\controller;


use app\AppFactory\AppFactory;

class SendCallback extends Common
{
    /**
     * 定时任务，1秒触发一次，对外API推送回调数据
     */
    public function send()
    {
        $app = AppFactory::api();
        for ($i = 0; $i < 8; $i++) {
            if ($i > 0) {
                $start = cache("start");
                if (!$start) break;
            }
            $app->callback->frequency = $i;
            $app->callback->initCallback();
            if ($i == 0  && $app->callback->callbackData) cache("start",1,7600);
            $app->callback->push();
        }
    }
}