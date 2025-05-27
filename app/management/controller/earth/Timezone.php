<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:51
 */

namespace app\management\controller\earth;


use app\management\controller\Common;

class Timezone extends Common
{
    /**
     * 获取时区列表
     * @return array|string
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["UTC" => "like","time_zone" => "like","country" => "like","main_cities" => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        return returnData($this->app->earth->getEarthTimezoneList($where,$pageNum,"*"));
    }
}