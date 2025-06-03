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

    protected $field = "me_id,m_id,machine_id,machine_name,address,error_position,errorCode,remark,create_time";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['machine_id' => "like","errorCode" => "like"]);
        $where['status'] = 1;
        return $this->app->machineErrorCode->getEcList($where,$pageNum,$this->field,'create_time desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['machine_id' => "like","errorCode" => "like"]);
        return $this->app->machineErrorCode->getFind($where,$this->field,'create_time desc');
    }

    /**
     * 确认已处理
     * @return array|mixed|\think\response\Json
     */
    public function confirmHandle()
    {
        $me_id = input('me_id');
        if (!$me_id) return returnValidate(lang("VMachineErrorCode.me_id_require"));
        return $this->app->machineErrorCode->confirmHandle($me_id);
    }

    /**
     * 获取系统日志
     * @return array|\think\response\Json
     */
    public function getErrorCodeLog()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['machine_id' => "like","errorCode" => "like"]);
        return $this->app->machineErrorCode->getEcList($where,$pageNum,$this->field,'create_time desc');
    }

    /**
     * 导出系统日志
     * @return array|bool|string|\think\response\Json
     */
    public function exportErrorCode()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['machine_id' => "like","errorCode" => "like"]);
        if (!isset($postData['create_time']) || !$postData['create_time']) $where[] = ['create_time','>=',strtotime("-1 month")];
        return $this->app->machineErrorCode->exportEc($where,$this->field,'create_time desc');
    }
}