<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/14
 * Time: 18:01
 */

namespace app\management\controller\auth;


use app\management\controller\Common;

class AuthManagerMachine extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\VAuth.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->authManagerMachine->getList($where,$pageNum, 'machine_id,machine_name');
    }

    public function bind()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'AuthManagerMachine_bind');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->authManagerMachine->bind($postData['manager_id'],$postData['m_ids']);
    }
}