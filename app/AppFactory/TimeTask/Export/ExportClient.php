<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/29
 * Time: 17:35
 */

namespace app\AppFactory\TimeTask\Export;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Export\ExportLogTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class ExportClient extends TimeTaskBase
{
    use ExportLogTrait;

    /**
     * 生成Excel文件并且修改记录
     * @param $data
     */
    public function makeExcel($data)
    {
        try {
            $data = json2arr($data);
            if ($data) {
                actionLog($data, '导出Excel的数据');
                $data['filename'] = $data['filename'] . date('His');
                $result = Excel::exportExcel($data['list'], $data['title'], $data['filename'], 0,
                    $data['otherData']['startRow'] ?? 1,
                    $data['otherData']['merge'] ?? []);
                $updateEL["export_id"] = $data['export_id'];
                $updateEL["file_name"] = $data['filename'];
                $updateEL["file_path"] = $result;
                $updateEL["export_time"] = time();
                $updateEL["status"] = 2;
                $this->updateExportLog($updateEL);
            }
        } catch (\PHPExcel_Writer_Exception $e) {
            actionException($e, 1);
            $this->updateExportLog(['export_id' => $data['export_id'], 'status' => 4]);
        } catch (\PHPExcel_Exception $e) {
            actionException($e, 1);
            $this->updateExportLog(['export_id' => $data['export_id'], 'status' => 4]);
        }
        @actionLog($this->getLS(), "【SQL】修改导出记录");
    }

    /**
     * 超过3天删除Excel表格
     * 定时任务：php think time_task export clearExcel
     * @return string
     */
    public function clearExcel()
    {
        $where[] = ['create_time', '<=', strtotime("-3 days")];
        $where[] = ['status','<',3];
        $log = $this->getExportLogList($where);
        if ($log) {
            $log = $log->toArray();
            foreach ($log as $k => $v) {
                if (file_exists(root_path() . 'public' . $v['file_path'])) {
                    @unlink(root_path() . 'public' . $v['file_path']);
                }
                $this->updateExportLog(['export_id' => $v['export_id'], 'status' => 3]);
                actionLog($this->getLS(),'修改导出记录');
            }
        }
        return "处理完成";
    }
}