<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/8/7
 * Time: 15:32
 */

namespace app\AppFactory\Kernel\Traits\Send;

use app\AppFactory\AppFactory;
use app\AppFactory\Notice\Application;

trait MobileNoticeTrait
{
    /**
     * @var Application
     */
    protected $nApp;

    /**
     * 初始化模板消息通知APP
     */
    protected function initMobileNoticeApp()
    {
        $this->nApp = AppFactory::notice();
    }


    public function sendMobileSupplementary($data)
    {
        $params = [
            "mobile" => $data['mobile'],
            "store_id" => $data['store_id'],
            "param" => [
                "" => $data[''],
            ],
        ];
        $this->initMobileNoticeApp();
        return $this->nApp->mobile->sendSupplementaryNotice($params);
    }
}