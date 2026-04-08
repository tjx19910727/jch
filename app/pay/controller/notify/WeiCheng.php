<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/1/29
 * Time: 10:07
 */

namespace app\pay\controller\notify;

use app\AppFactory\AppFactory;

class WeiCheng
{

    public function scanNotify(){
        //用户信息入库等。
        $postData = input();
        $postData = json2arr($postData);
        actionLog($postData, '微程退款推送数据');
        return true;
    }
    
    //最新商品信息同步
    public function syncGoodsInfo(){
		//用户信息入库等。
        $postData = input();
        $postData = json2arr($postData);
        actionLog($postData, '最新商品数据');
        return true;
    }

    public function refund()
    {
        try {
            $postData = input();
            $postData = json2arr($postData);
            actionLog($postData, '微程退款推送数据');
            //调用后台退款接口
        } catch (\Exception $e) {
            actionException($e,1);
            echo  "ok";
            die();
        }
    }

    public function refundAll()
    {
        try {
            $postData = input();
            $postData = json2arr($postData);
            actionLog($postData, '微程退款推送数据');
            //调用后台退款接口
        } catch (\Exception $e) {
            actionException($e,1);
            echo  "ok";
            die();
        }
    }
}