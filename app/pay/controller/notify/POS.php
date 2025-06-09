<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/6/7
 * Time: 14:53
 */

namespace app\pay\controller\notify;


use app\AppFactory\AppFactory;
use app\pay\validate\VPos;

class POS
{
    public function posNotify()
    {
//        try {
            $postData = input();
            $postData = json2arr($postData);
            actionLog($postData, '回调通知数据');
            validate(VPos::class . ".posNotify")->check($postData);
            return AppFactory::pay($postData)->posNotify->handlePos();
//        } catch (\Exception $e) {
//            actionException($e,1);
//            return returnState(300,$e->getMessage());
//        }
    }
}