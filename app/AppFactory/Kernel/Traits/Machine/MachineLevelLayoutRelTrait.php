<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/7/1
 * Time: 15:46
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineLevelLayoutRelModel;

trait MachineLevelLayoutRelTrait
{
    public function getMachineLevelLayoutRelFind($where, $field = "*", $order = "")
    {
        return MachineLevelLayoutRelModel::getFind($where, $field, $order);
    }

    public function getMachineLevelLayoutRelList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return MachineLevelLayoutRelModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function getMachineLevelLayoutRelCount($where)
    {
        return MachineLevelLayoutRelModel::getCount($where);
    }

    public function addMachineLevelLayoutRel($insert)
    {
        $data = MachineLevelLayoutRelModel::create($insert);
        return $data->id;
    }

    public function addMachineLevelLayoutRelAll($insertAll)
    {
        $model = new MachineLevelLayoutRelModel();
        return $model->saveAll($insertAll);
    }

    public function delMachineLevelLayoutRel($where)
    {
        return MachineLevelLayoutRelModel::whereDel($where);
    }

    public function getLayoutModelIdsByLevel($machineLevel)
    {
        $where = [];
        if (is_array($machineLevel)) {
            $where[] = ['machine_level', 'in', $machineLevel];
        } else {
            $where['machine_level'] = $machineLevel;
        }
        return MachineLevelLayoutRelModel::getColumn($where, 'mlm_id');
    }

    public function getLevelsByLayoutModelId($mlmId)
    {
        return MachineLevelLayoutRelModel::getColumn(['mlm_id' => $mlmId], 'machine_level');
    }

    protected function _saveLevelLayoutRel($machineLevel, $mlmIds)
    {
        $this->delMachineLevelLayoutRel(['machine_level' => $machineLevel]);
        if (!empty($mlmIds)) {
            $insertAll = [];
            $now = time();
            foreach ($mlmIds as $mlmId) {
                $insertAll[] = [
                    'machine_level' => intval($machineLevel),
                    'mlm_id' => intval($mlmId),
                    'create_time' => $now,
                    'update_time' => $now,
                ];
            }
            $this->addMachineLevelLayoutRelAll($insertAll);
        }
    }
}