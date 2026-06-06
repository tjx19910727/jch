<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/27
 * Time: 16:47
 */

namespace app\AppFactory\Kernel\Traits\Export;



use app\AppFactory\Kernel\Model\Export\ExportLogModel;
use app\AppFactory\RabbitMq\MqProducer;

trait ExportLogTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getExportLogValue($where, $value)
    {
        return ExportLogModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getExportLogColumn($where, $column)
    {
        return ExportLogModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getExportLogCount($where)
    {
        return ExportLogModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getExportLogList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return ExportLogModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getExportLogFind($where, $field = "*", $order = "")
    {
        return ExportLogModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addExportLog($insert)
    {
        $data = ExportLogModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return ExportLogModel
     */
    public function updateExportLog($update,$where = [],$field = [])
    {
        return ExportLogModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delExportLog($where)
    {
        return ExportLogModel::whereDel($where);
    }

    public function sendToExport($position,$filename,$title,$list,$otherData = [])
    {
        $insert = [
            "request_time" => time(),
            "export_position" => $position,
            "file_name" => $filename,
            "status" => 1,
            "ao_id" => $this->manager['ao_id'],
            "creator" => $this->manager['manager_id'],
            "create_time" => time(),
        ];
        $export_id = $this->addExportLog($insert);
        if ($export_id) {
            $data = [
                "export_id" => $export_id,
                "filename" => $filename,
                "title" => $title,
                "list" => $list,
                "otherData" => $otherData,
            ];
            actionLog([
                'export_id' => $export_id,
                'filename' => $filename,
                'title_count' => count($title),
                'row_count' => count($list),
            ], '导出任务摘要');
            $result = MqProducer::export($data);
            if ($result != "OK") {
                $this->updateExportLog(['export_id' => $export_id,'status' => 4]);
                return $this->rFail($result);
            }
            return $this->r(200,$this->lang("export.exporting"));
        }
        return $this->rFail($this->lang("export.export_log_create_fail"));
    }
}
