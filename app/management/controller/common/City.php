<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/10
 * Time: 11:16
 */

namespace app\management\controller\common;


use app\management\controller\Common;

class City extends Common
{
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $pageNum = $postData['pageNum'] ?? 0;
        $where['city_pid'] = $postData['city_pid'] ?? 0;
        return $this->app->city->getList($where,$pageNum,'*','city_id asc');
    }

    public function getFind()
    {
        $city_id = input('city_id');
        return $this->app->city->getFind(['city_id' => $city_id]);
    }
}