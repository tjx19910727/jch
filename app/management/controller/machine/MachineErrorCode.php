<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/30
 * Time: 14:13
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineErrorCode extends Common
{

    protected $field = "me_id,m_id,machine_id,machine_name,address,errorCode,remark,create_time";
    protected $validatePath = 'app\management\validate\V.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['machine_id' => "like","errorCode" => "like"]);
        return $this->app->machineErrorCode->getList($where,$pageNum,$this->field,'create_time desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['machine_id' => "like","errorCode" => "like"]);
        return $this->app->machineErrorCode->getFind($where,$this->field,'create_time desc');
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return;
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return;
    }
}