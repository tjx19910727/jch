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
//        $where = $this->getWhere([]);
        //$data = $this->app->machineChannel->getData();
        $data = $this->app->machineChannel->getDataV2();
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
     * 导出空槽列表
     * @return array|\think\response\Json
     */
    public function exportEmptyChannel()
    {
        $where = $this->getWhere([]);
        return $this->app->machineChannel->exportEmptyList($where);
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
     * 导出Bad列表
     * @return array|\think\response\Json
     */
    public function exportBadChannel()
    {
        $where = $this->getWhere([]);
        return $this->app->machineChannel->exportBadList($where);
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
     * 导出空货列表
     * @return array|\think\response\Json
     */
    public function exportStockOutChannel()
    {
        $where = $this->getWhere([]);
        return $this->app->machineChannel->exportStockOutList($where);
    }

    /**
     * 礼品赠送，今天/昨天
     * @return array|\think\response\Json
     */
    public function getGift()
    {
        $where = $this->getWhere([],false,[],'so.');
        return $this->app->saleOrders->getGift($where);
    }

    /**
     * 获取销售折线图数据
     * @return array|string
     */
    public function getSaleChart()
    {
        $type = input('type');
        $where = $this->getWhere([]);
        return $this->app->saleOrders->getChartData($where,$type);
    }

    /**
     * 获取设备前10排行榜
     * @return array|string
     */
    public function getMachine10List()
    {
        $where = $this->getWhere([]);
        $where[] = ['countDate','>=',strtotime("-7 days")];
        return $this->app->machine->get10List($where);
    }

    /**
     * 导出设备排行榜
     * @return array|\think\response\Json
     */
    public function exportMachineList()
    {
        $where = $this->getWhere([]);
        $where[] = ['countDate','>=',strtotime("-7 days")];
        return $this->app->machine->exportRankingList($where);
    }

    /**
     * 获取商品前10排行榜
     * @return array|string
     */
    public function getGoods10List()
    {
        $where = $this->getWhere([]);
        $where[] = ['countDate','>=',strtotime("-7 days")];
        return $this->app->goods->get10List($where);
    }

    /**
     * 导出商品排行榜
     * @return array|\think\response\Json
     */
    public function exportGoodsList()
    {
        $where = $this->getWhere([]);
        $where[] = ['countDate','>=',strtotime("-7 days")];
        return $this->app->goods->exportRankingList($where);
    }

    /**
     * 获取设备排行榜（V2）
     * 支持时间范围、设备编号（多选）、设备分组、国家/省/市筛选、pageNum分页
     * @return array|string
     */
    public function getMachineTopList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 15;
        $topType = $postData['top_type'] ?? 1;
        if (!in_array($topType, [1, 2], true)) {
            $topType = 1;
        }
        $where = $this->buildRankingWhereV2($postData, true);
        return $this->app->machine->getRankingList($where, $pageNum, $topType);
    }

    /**
     * 获取商品排行榜（V2）
     * 支持时间范围、设备编号（多选）、设备分组、国家/省/市筛选、pageNum分页
     * @return array|string
     */
    public function getGoodsTopList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 15;
        $topType = $postData['top_type'] ?? 1;
        if (!in_array($topType, [1, 2], true)) {
            $topType = 1;
        }
        $where = $this->buildRankingWhereV2($postData, false);
        return $this->app->goods->getRankingList($where, $pageNum, $topType);
    }

    /**
     * 导出设备排行榜（V2）
     * 筛选条件与 getMachineTopList 一致
     * @return array|\think\response\Json
     */
    public function exportMachineListV2()
    {
        $postData = input();
        $topType = $postData['top_type'] ?? 1;
        if (!in_array($topType, [1, 2], true)) {
            $topType = 1;
        }
        $where = $this->buildRankingWhereV2($postData, true);
        return $this->app->machine->exportRankingListV2($where, $topType);
    }

    /**
     * 导出商品排行榜（V2）
     * 筛选条件与 getGoodsTopList 一致
     * @return array|\think\response\Json
     */
    public function exportGoodsListV2()
    {
        $postData = input();
        $topType = intval($postData['top_type'] ?? 1);
        if (!in_array($topType, [1, 2], true)) {
            $topType = 1;
        }
        $where = $this->buildRankingWhereV2($postData, false);
        return $this->app->goods->exportRankingListV2($where, $topType);
    }

    /**
     * 商品滞销设备列表
     * 入参支持：m_id(逗号分隔/数组)、machine_group_id、countDate、sale_count
     * @return array|string
     */
    public function getSlowMovingGoodsList()
    {
        $postData = input();
        $where = $this->getWhere([]);
        $postData['so_where'] = $this->formatAoIdWhereWithPrefix($where, 'so.');
        return $this->app->machineChannel->getSlowMovingGoodsList($postData);
    }

    /**
     * 导出商品滞销设备列表
     * 筛选条件与 getSlowMovingGoodsList 一致
     * @return array|\think\response\Json
     */
    public function exportSlowMovingGoodsList()
    {
        $postData = input();
        $where = $this->getWhere([]);
        $postData['so_where'] = $this->formatAoIdWhereWithPrefix($where, 'so.');
        return $this->app->machineChannel->exportSlowMovingGoodsList($postData);
    }

    /**
     * 商品滞销设备货道详情
     * 传入 m_id，返回当前设备满足筛选条件的滞销货道与空货货道
     * @return array|string
     */
    public function getSlowMovingGoodsDetail()
    {
        $postData = input();
        $where = $this->getWhere([]);
        $postData['so_where'] = $this->formatAoIdWhereWithPrefix($where, 'so.');
        return $this->app->machineChannel->getSlowMovingGoodsDetail($postData);
    }

    /**
     * 组装V2排行榜筛选条件
     * @param array $postData
     * @param bool $forMachine 是否设备排行榜
     * @return array
     */
    private function buildRankingWhereV2($postData, $forMachine = true)
    {
        $where = $this->getWhere([]);

        $dateRange = '';
        if (isset($postData['countDate']) && $postData['countDate']) {
            $dateRange = $postData['countDate'];
        }

        if ($dateRange) {
            $parts = explode('~', $dateRange);
            if (isset($parts[0]) && isset($parts[1])) {
                $startTime = strtotime(trim($parts[0]));
                $endTime = strtotime(trim($parts[1]));
                if ($startTime !== false && $endTime !== false) {
                    $where[] = ['countDate', 'between', [$startTime, $endTime]];
                }
            }
        }

        $mIds = $this->resolveRankingMIds($postData);
        if ($mIds !== null) {
            if (!$mIds) {
                $where[] = ['m_id', '=', 0];
                return $where;
            }
            $where[] = ['m_id', 'in', $mIds];
        }

        return $where;
    }

    /**
     * 根据设备编号/分组/国家省市解析设备ID列表
     * @param array $postData
     * @return array|null null表示没有设备相关筛选
     */
    private function resolveRankingMIds($postData)
    {
        $machineWhere = [];
        $hasMachineFilter = false;

        if (isset($postData['m_id']) && $postData['m_id'] !== '') {
            $mIdValue = $postData['m_id'];
            if (!is_array($mIdValue)) {
                $mIdValue = explode(',', (string)$mIdValue);
            }
            $mIdValue = array_values(array_filter(array_map('intval', $mIdValue), function ($v) {
                return $v > 0;
            }));
            if ($mIdValue) {
                $machineWhere[] = ['m_id', 'in', $mIdValue];
                $hasMachineFilter = true;
            }
        }

        if (isset($postData['machine_group_id']) && $postData['machine_group_id'] !== '') {
            $groupMIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']], 'm_id');
            if ($groupMIds) {
                $machineWhere[] = ['m_id', 'in', $groupMIds];
                $hasMachineFilter = true;
            }

        }

        foreach (['country_id', 'state_id', 'city_id'] as $addressField) {
            if (!isset($postData[$addressField]) || empty($postData[$addressField])) {
                continue;
            }
            $value = $postData[$addressField];
            $machineWhere[] = [$addressField, '=', $value];
            $hasMachineFilter = true;
        }

        if (!$hasMachineFilter) {
            return null;
        }

        $mIds = $this->app->machine->getMachineColumn($machineWhere, 'm_id');
        if (!$mIds) {
            return [];
        }
        return array_values(array_unique($mIds));
    }
}