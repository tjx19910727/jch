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

    /**
     * 获取策略列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStrategyMachineList($where,$pageNum = 0,$field = "*", $order = "")
    {
        $data = StrategyMachineModel::getList($where,$pageNum,$field,$order,function ($item) {
            if (isset($item['m_id'])) {
                $item['machine_id'] = $this->getMachineValue(['m_id' => $item['m_id']],'machine_id');
            }
            if (isset($item['s_id']) && isset($item['s_type'])) {
                switch ($item['s_type']) {
                    // 收款策略
                    case 1:
                        $item['name'] = $this->getStrategyPayeeValue(['sp_id' => $item['s_id']], 'sp_name');
                        break;
                    // 分润策略
                    case 3:
                        $item['name'] = $this->getStrategyIncomeValue(['si_id' => $item['s_id']], 'income_name');
                        break;
                    default:
                        $item['name'] = "";
                        break;
                }
            }
            return $item;
        });
        return $data;
    }

    public function addStrategyMachine($insert)
    {
        $this->startTrans();
        try {
            $flag = [];
            $m_id = explode(",", $insert['m_id']);
            foreach ($m_id as $key => $value) {
                $ao_id = $this->getMachineValue(['m_id' => $value],'ao_id');
                $sm = $this->getStrategyMachineFind(['s_id' => $insert['s_id'], 'm_id' => $value, 's_type' => $insert['s_type'], 'ao_id' => $ao_id ], 'sm_id');
                if (!$sm) {
                    $insert['m_id'] = $value;
                    $insert['ao_id'] = $ao_id;
                    $data = StrategyMachineModel::create($insert);
                    $flag[] = $data->sm_id;
                    continue;
                }
            }
            $result = flag_check($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
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
    public function getMachinePayeeFind($where,$field = "sp.*",$order = "sm.sort asc")
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