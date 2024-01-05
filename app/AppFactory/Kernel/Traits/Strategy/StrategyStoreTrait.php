<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:38
 */

namespace app\AppFactory\Kernel\Traits\Strategy;


use app\AppFactory\Kernel\Model\Strategy\StrategyStoreModel;

trait StrategyStoreTrait
{
    public function getStrategyStoreFind($where, $field = "*",$order = "")
    {
        $data = StrategyStoreModel::getFind($where,$field,$order);
        return $data;
    }

    public function getStrategyStoreList($where,$pageNum = 0,$field = "*", $order = "")
    {
        $data = StrategyStoreModel::getList($where,$pageNum,$field,$order);
        return $data;
    }

    public function addStrategyStore($insert)
    {
        $this->startTrans();
        $flag = [];
        $store_id = explode(",",$insert['store_id']);
        foreach ($store_id as $key => $value) {
            $ss = $this->getStrategyStoreFind(['s_id' => $insert['s_id'],'store_id' => $value,'s_type' => $insert['s_type']],'ss_id');
            if (!$ss) {
                $insert['store_id'] = $value;
                $data = StrategyStoreModel::create($insert);
                $flag[] = $data->ss_id;
                continue;
            }
            $flag[] = $ss['ss_id'];
        }
        $result = flag_check($flag);
        return $this->checkTrans($result);
    }

    public function updateStrategyStore($update,$where = [], $field = [])
    {
        return StrategyStoreModel::update($update,$where,$field);
    }

    public function delStrategyStore($where)
    {
        return StrategyStoreModel::destroy($where);
    }

    public function getStrategyStoreColumn($where,$column)
    {
        return StrategyStoreModel::getColumn($where,$column);
    }

    /**
     * 获取一条微信公众号授权信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return StrategyStoreModel|array|mixed|null|string|\think\Model
     */
    public function getWxFind($where,$field = "wx.*",$order = "ss.sort asc")
    {
        return StrategyStoreModel::getStrategyFind($where,$field,$order);
    }

    /**
     * 获取一条收款方配置信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return StrategyStoreModel|array|mixed|null|string|\think\Model
     */
    public function getStorePayeeFind($where,$field = "sp.*",$order = "ss.sort asc")
    {
        $result = StrategyStoreModel::getStrategyFind($where,$field,$order);
        return $result;
    }

    /**
     * 条件获取门店绑定的策略数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return StrategyStoreModel|array|mixed|null|string|\think\Model
     */
    public function getStrategyByCondition($where,$field = "*",$order = "ss.sort asc")
    {
        $result = StrategyStoreModel::getStrategyFind($where,$field,$order);
        return $result;
    }
    
}