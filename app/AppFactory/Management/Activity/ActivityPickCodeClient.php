<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:38
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickTrait;
use app\AppFactory\Management\ManagementClient;

class ActivityPickCodeClient extends ManagementClient
{
    use ActivityPickCodeTrait,ActivityPickTrait,ActivityGoodsTrait,ActivityMachineTrait;


    /**
     * 批量添加
     * @param $postData
     * @return array|string
     * @throws \Exception
     */
    public function addMore($postData)
    {
        $field = "id,start_time,end_time,pick_type,status";
        $pick = $this->getActivityPickFind(['id' => $postData['id']], $field);
        if (!$pick) return $this->r(100, $this->lang("VActivityPickCode.pick_code_no_data"));
        $pick = $pick->toArray();
        if ($pick['status'] == 3) return $this->r(100,$this->lang("VActivityPickCode.status3"));
        if ($pick['status'] == 4) return $this->r(100,$this->lang("VActivityPickCode.status4"));
        $update = [];
        if ($pick['start_time'] > time()) {
            $update['status'] = 2;
        }
        if ($pick['end_time'] > 0 && $pick['end_time'] < time()) {
            $update['status'] = 3;
            $this->updateActivityPick($update);
            return $this->r(100,$this->lang("VActivityPickCode.status3"));
        }
        if ($update) {
            $update['id'] = $pick['id'];
            $this->updateActivityPick($update);
        }

        $insertAll = [];
        $codeList = $this->getActivityPickCodeColumn([], 'code');
        for ($i = 0; $i < $postData['quantity']; $i++) {
            $insert['ap_id'] = $pick['id'];
            $insert['pick_type'] = $pick['pick_type'];
            while (1) {
                $code = $this->leftHandZero(random_int(00000000, 99999999), 8);
                if (!in_array($code, $codeList)) {
                    $codeList[] = $code;
                    break;
                }
            }
            $insert['code'] = $code;
            $insertAll[] = $insert;
        }
        $result = $this->addActivityPickCodeMore($insertAll);
        return $this->rAction($result);
    }

    /**
     * 导出未使用提货码
     * @param $postData
     * @return array|string
     */
    public function exportCode($postData)
    {
        try {
            $ap = $this->getActivityPickFind(['id' => $postData['id']], "pick_name");
            if (!$ap) return $this->r(100, '查无提货码活动信息');
            $codeList = $this->getActivityPickCodeList(['ap_id' => $postData['id'],'status' => 1], 0, 'code');
            if ($codeList) {
                $codeList = $codeList->toArray();
                $title = ["code" => "提货码"];
                $filename = "导出【" . $ap['pick_name'] . "】提货码-" . date("YmdHis");
                $result = Excel::exportExcel($codeList, $title, $filename);
                return $this->r(200, '导出成功', $result);
            }
            return $this->rFail("查无提货码");
        } catch (\PHPExcel_Writer_Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        } catch (\PHPExcel_Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 导出使用报表
     * @param $postData
     * @return array|string
     */
    public function exportUsedList($postData)
    {
        try {
            $ap = $this->getActivityPickFind(['id' => $postData['id']], "pick_name,`desc`");
            if (!$ap) return $this->r(100, '查无提货码信息');
            $usedList = $this->getActivityPickCodeList(['ap_id' => $postData['id']], 0,
                '("' . $ap['pick_name'] . '") pick_name,("' . $ap['desc'] . '") `desc`,machine_id,machine_name,
                code,trade_no,
                (CASE pick_type WHEN 1 THEN "未使用" WHEN 2 THEN "已使用" WHEN 3 THEN "已过期" WHEN 4 THEN "已作废" WHEN 5 THEN "使用中" END ) status, used_time');
            if ($usedList) {
                $usedList = $usedList->toArray();
                $title = [
                    "pick_name" => "提货码活动名称",
                    "desc" => "简介",
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "code" => "提货码",
                    "status" => "激活状态",
                    "trade_no" => "订单编号",
                    "used_time" => "使用时间",
                ];
                $filename = "导出【" . $ap['pick_name'] . "】使用报表-" . date("Ymd");
                $result = Excel::exportExcel($usedList, $title, $filename);
                return $this->r(200, '导出成功', $result);
            }
            return $this->rFail("查无使用报表信息");
        } catch (\PHPExcel_Writer_Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        } catch (\PHPExcel_Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }
}