<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/30
 * Time: 14:14
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Machine\MachineErrorCodeTrait;
use app\AppFactory\Management\ManagementClient;

class MachineErrorCodeClient extends ManagementClient
{
    use MachineErrorCodeTrait;

    /**
     * 确认处理
     * @param $me_id
     * @return array|\think\response\Json
     */
    public function confirmHandle($me_id)
    {
        $me = $this->getMachineErrorCodeFind(['me_id' => $me_id]);
        $update = ['status' => 2];
        $where['errorCode'] = $me['errorCode'];
        $where['m_id'] = $me['m_id'];
        return $this->rAction($this->updateMachineErrorCode($update, $where));
    }

    protected $errorPosition = [
        "1" => "机器结构",
        "2" => "控制主板",
        "3" => "工控电脑",
    ];

    public function getEcList($where, $pageNum = 0, $field = "*", $order = "", $group = "")
    {
        $data = $this->getMachineErrorCodeList($where, $pageNum, $field, $order, '', $group);
        return $this->rQ($data);
    }

    /**
     * 导出系统日志
     * @param $where
     * @param string $field
     * @param string $order
     * @return array|bool|string|\think\response\Json
     */
    public function exportEc($where, $field = "*", $order = "create_time desc")
    {
        $list = $this->getMachineErrorCodeList($where, 0, $field . ",msg", $order);
        if ($list) {
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                $v['error_position'] = $this->errorPosition[$v['error_position']];
                $list[$k] = $v;
            }
            $title = [
                "machine_id" => "设备编号",
                "machine_name" => "设备编号",
                "error_position" => "位置",
                "errorCode" => "类型",
                "remark" => "简介",
                "msg" => "说明",
                "create_time" => "记录时间",
            ];
            $filename = "系统事件-" . date("YmdHis");
            return $this->sendToExport("事件日志-系统日志", $filename, $title, $list);
        }
        return $this->rNoData();
    }
}