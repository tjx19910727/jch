<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/30
 * Time: 10:53
 */

namespace app\management\controller\export;


use app\management\controller\Common;

class ExportLog extends Common
{

    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["export_position" => "like"]);
        return $this->app->exportLog->getList($where,$pageNum,$this->field,'create_time desc');
    }
}