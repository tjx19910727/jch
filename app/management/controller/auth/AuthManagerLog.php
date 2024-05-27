<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/25
 * Time: 15:45
 */

namespace app\management\controller\auth;


use app\management\controller\Common;

class AuthManagerLog extends Common
{

    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->authManagerLog->getMlList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return;
    }
}