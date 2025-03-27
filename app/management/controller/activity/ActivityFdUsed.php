<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/26
 * Time: 11:37
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityFdUsed extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\V.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        $where[] = ['used_time','>',0];
        return $this->app->activityFdUsed->getList($where,$pageNum,$this->field);
    }

    public function exportUsed()
    {
        $fd_id = input("fd_id");
        return $this->app->activityFdUsed->exportList($fd_id);
    }
}