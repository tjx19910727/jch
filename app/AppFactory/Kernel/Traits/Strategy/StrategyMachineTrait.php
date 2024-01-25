<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:38
 */

namespace app\AppFactory\Kernel\Traits\Strategy;


use app\AppFactory\Kernel\Model\Strategy\StrategyMachineModel;

trait StrategyMachineTrait
{
    public function getStrategyMachineFind($where, $field = "*",$order = "")
    {
        $data = StrategyMachineModel::getFind($where,$field,$order);
        return $data;
    }

    public function getStrategyMachineList($where,$pageNum = 0,$field = "*", $order = "")
    {
        $data = StrategyMachineModel::getList($where,$pageNum,$field,$order);
        return $data;
    }

    public function addStrategyMachine($insert)
    {
        $this->startTrans();
        $flag = [];
        $store_id = explode(",",$insert['m_id']);
        foreach ($store_id as $key => $value) {
            $sm = $this->getStrategyMachineFind(['s_id' => $insert['s_id'],'m_id' => $value,'s_type' => $insert['s_type']],'sm_id');
            if (!$sm) {
                $insert['store_id'] = $value;
                $data = StrategyMachineModel::create($insert);
                $flag[] = $data->sm_id;
                continue;
            }
            $flag[] = $sm['sm_id'];
        }
        $result = flag_check($flag);
        return $this->checkTrans($result);
    }

    public function updateStrategyMachine($update,$where = [], $field = [])
    {
        return StrategyMachineModel::update($update,$where,$field);
    }

    public function delStrategyMachine($where)
    {
        return StrategyMachineModel::destroy($where);
    }

    public function getStrategyMachineColumn($where,$column)
    {
        return StrategyMachineModel::getColumn($where,$column);
    }

    /**
     * 获取一条微信公众号授权信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return StrategyMachineModel|array|mixed|null|string|\think\Model
     */
    public function getWxFind($where,$field = "wx.*",$order = "sm.sort asc")
    {
        return StrategyMachineModel::getStrategyFind($where,$field,$order);
    }

    /**
     * 获取一条收款方配置信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return StrategyMachineModel|array|mixed|null|string|\think\Model
     */
    public function getStorePayeeFind($where,$field = "sp.*",$order = "sm.sort asc")
    {
        $result = StrategyMachineModel::getStrategyFind($where,$field,$order);
        return $result;
    }

    /**
     * 条件获取门店绑定的策略数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return StrategyMachineModel|array|mixed|null|string|\think\Model
     */
    public function getStrategyByCondition($where,$field = "*",$order = "sm.sort asc")
    {
        $result = StrategyMachineModel::getStrategyFind($where,$field,$order);
        return $result;
    }
    
}