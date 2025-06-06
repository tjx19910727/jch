<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/10
 * Time: 11:11
 */

namespace app\AppFactory\Management\Advertisement;


use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementRecordTrait;
use app\AppFactory\Management\ManagementClient;

class AdvertisementRecordClient extends ManagementClient
{
    use AdvertisementRecordTrait;

    public function getArList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $data = $this->getAdvertisementRecordList($where,$pageNum,$field,$order,function ($item) {
            if ($item['total_times'] == 0) $item['total_times'] = "无限";
            return $item;
        });
        return $this->rQ($data);
    }
}