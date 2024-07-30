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

    // 生成Excel文件并且生成记录
    public function makeExcel($data)
    {
        $result = Excel::exportExcel($data['list'], $data['title'], $data['filename']);
        $updateEL["export_id"] =  $data['export_id'];
        $updateEL["file_name"] =  $data['filename'];
        $updateEL["file_path"] =  $result;
        $updateEL["export_time"] =  time();
        $updateEL["status"] =  2;
        $this->updateExportLog($updateEL);
    }

    /**
     * 超过3天删除Excel表格
     */
    public function delExcel()
    {
        $where[] = ['create_time','<', strtotime("-3 days")];
        $log = $this->getExportLogList($where);
        if ($log) {
            $log = $log->toArray();
            foreach ($log as $k => $v) {
                if (file_exists(public_path() . $v['file_path'])) {
                    @unlink(public_path() . $v['file_path']);
                }
                $this->updateExportLog(['export_id' => $v['export_id'],'status' => 3]);
            }
        }
    }
}