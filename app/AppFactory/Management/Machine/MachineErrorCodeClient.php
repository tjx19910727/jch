<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/30
 * Time: 14:14
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Model\Auth\AuthManagerModel;
use app\AppFactory\Kernel\Model\Machine\MachineErrorCodeModel;
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

        public function getEcFind($where, $pageNum = 0, $field = "*", $order = "", $group = "")
    {
        $data = $this->getMachineErrorCodeFind($where, $pageNum, $field, $order, function ($item) {
            $errTypeName = "";
            if (isset($item['errorCode']) && $item['errorCode']) {
                $errTypeList = config("error_code_list");
                foreach ($errTypeList as $value) {
                    if (in_array($item['errorCode'], $value['codeList'])) {
                        $errTypeName = $this->lang("deviceErrorCodeType." . $value['name']);
                        break;
                    }
                }
            }
            $item['errorCodeType'] = $errTypeName;
            return $item;
        }, $group);
        actionLog($this->getLS(),'【SQL】查询错误码列表');
        return $this->rQ($data);
    }


    public function getEcList($where, $pageNum = 0, $field = "*", $order = "", $group = "")
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) {
                $where[] = ['m_id', 'in', $mIds];
            }
        }
        $data = $this->getMachineErrorCodeList($where, $pageNum, $field, $order, function ($item) {
            $errTypeName = "";
            if (isset($item['errorCode']) && $item['errorCode']) {
                $errTypeList = config("error_code_list");
                foreach ($errTypeList as $value) {
                    if (in_array($item['errorCode'], $value['codeList'])) {
                        $errTypeName = $this->lang("deviceErrorCodeType." . $value['name']);
                        break;
                    }
                }
            }
            $item['errorCodeType'] = $errTypeName;
            return $item;
        }, $group);
        actionLog($this->getLS(),'【SQL】查询错误码列表');
        return $this->rQ($data);
    }

    /**
     * 视频统一入口列表
     * 不做错误码分类翻译，返回 v_type 供前端识别视频类型
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $group
     * @return array|\think\response\Json
     */
    public function getEcVideoList($where, $pageNum = 0, $field = "*", $order = "", $group = "")
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) {
                $where[] = ['m_id', 'in', $mIds];
            }
        }

        // 批量获取 creator_id 对应的管理员昵称，避免循环查询
        $creatorIds = MachineErrorCodeModel::getColumn($where, 'creator_id');
        $creatorIds = array_unique(array_filter($creatorIds));
        $creatorNames = [];
        if ($creatorIds) {
            $creatorNames = AuthManagerModel::getColumn(['manager_id' => ['in', $creatorIds]], 'nickname', 'manager_id');
        }
        $openArr = ['1200000' =>'1', '1200010' => '2', '1200020' => '3']; //开门方式
        $data = $this->getMachineErrorCodeList($where, $pageNum, $field, $order, function ($item) use ($creatorNames, $openArr) {
            $item['v_type'] = 0;
            if (isset($item['errorCode']) && in_array($item['errorCode'], [1200000, 1200010, 1200020])) {
                $item['v_type'] = 1;
            }
            $item['open_type'] = $openArr[$item['errorCode']] ?? 1;
            $item['creator_name'] = $creatorNames[$item['creator_id']] ?? '';
            $item['create_time'] =  date("Y-m-d H:i:s", $item['create_time']);
            return $item;
        }, $group);

        actionLog($this->getLS(),'【SQL】查询视频统一入口/开锁日志列表');
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
        $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
        if ($mIds) {
            $where[] = ['m_id', 'in', $mIds];
            $field = str_replace("create_time","FROM_UNIXTIME(create_time) create_time",$field);
            $list = $this->getMachineErrorCodeList($where, 0, $field . ",msg", $order);
            if ($list) {
                $list = $list->toArray();
                foreach ($list as $k => $v) {
                    $v['error_position'] = $this->errorPosition[$v['error_position']];
                    $list[$k] = $v;
                }
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "error_position" => "错误位置",
                    "errorCode" => "错误码类型",
                    "remark" => "简介",
                    "create_time" => "上报时间",
                ];
                $filename = "系统事件-" . date("YmdHis");
                return $this->sendToExport("事件日志-系统日志", $filename, $title, $list);
            }
        }
        return $this->rNoData();
    }
}