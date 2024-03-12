<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/15
 * Time: 9:31
 */

namespace app\management\controller;


class Index extends Common
{
    /**
     * 获取昨天、今天销售额与销量
     * @return array|string
     */
    public function getSaleData()
    {
        $where = $this->getWhere(["pay_status" => 3]);
        $data = $this->app->saleOrders->getData($where);
        return returnState(200,'查询成功',$data);
    }

    /**
     * 获取设备数据
     * @return array|string
     */
    public function getMachineData()
    {
        $where = $this->getWhere([]);
        $data = $this->app->machine->getData($where);
        return returnState(200,'查询成功',$data);
    }

    /**
     * 获取货道数据
     * @return array|string
     */
    public function getChannelData()
    {
        $where = $this->getWhere([]);
        $data = $this->app->machineChannel->getData($where);
        return returnState(200,'查询成功',$data);
    }

    /**
     * 获取空槽列表
     * @return array|string
     */
    public function getEmptyChannel()
    {
        $where = $this->getWhere([]);
        return $this->app->machineChannel->getEmptyList($where);
    }

    /**
     * 获取Bad列表
     * @return array|string
     */
    public function getBadChannel()
    {
        $where = $this->getWhere([]);
        return $this->app->machineChannel->getBadList($where);
    }

    /**
     * 获取空货列表
     * @return array|string
     */
    public function getStockOutChannel()
    {
        $where = $this->getWhere([]);
        return $this->app->machineChannel->getStockOutList($where);
    }

    /**
     * 获取销售折线图数据
     * @return array|string
     */
    public function getSaleChart()
    {
        $type = input('type');
        $where['ao_id'] = $this->manager['ao_id'];
        return $this->app->saleOrders->getChartData($where,$type);
    }

    /**
     * 获取设备前10排行榜
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMachine10List()
    {
        $where['ao_id'] = $this->manager['ao_id'];
        $where[] = ['countDate','>=',strtotime("-7 days")];
        return $this->app->machine->get10List($where);
    }

    /**
     * 获取商品前10排行榜
     * @return array|string
     */
    public function getGoods10List()
    {
        $where['ao_id'] = $this->manager['ao_id'];
        $where[] = ['countDate','>=',strtotime("-7 days")];
        return $this->app->goods->get10List($where);
    }
}