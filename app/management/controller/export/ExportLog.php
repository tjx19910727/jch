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
        $keyword = trim(strval($postData['keyword'] ?? ''));
        unset($postData['keyword']);
        $where = $this->getWhere($postData, false, ["export_position" => "like"]);
        if ($keyword !== '') {
            $nicknameManagerIds = $this->app->authManager->getAuthManagerColumn([
                ['nickname', 'like', "%{$keyword}%"],
            ], 'manager_id');
            $accountManagerIds = $this->app->authManager->getAuthManagerColumn([
                ['account', 'like', "%{$keyword}%"],
            ], 'manager_id');
            $managerIds = array_values(array_unique(array_merge($nicknameManagerIds, $accountManagerIds)));
            $where[] = ['creator', 'in', $managerIds ?: [0]];
        }
        return $this->app->exportLog->getList($where,$pageNum,$this->field,'create_time desc');
    }
}
