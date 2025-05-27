<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/15
 * Time: 9:32
 */

namespace app\AppFactory\Management\Index;


use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Store\StoreTrait;
use app\AppFactory\Management\ManagementClient;

class SaleDataClient extends ManagementClient
{
    use SaleOrdersTrait;
    use StoreTrait;

    public function initWhere(&$where)
    {
        $childIds = $this->app->authManager->getChildIdList($this->manager['manager_id']);
        $childIds[] = $this->manager['manager_id'];
        $storeIds = $this->getStoreColumn([['store_manager','in',$childIds]],'store_id');
        $where[] = ['store_id', 'in', $storeIds];
    }

    /**
     * 获取销售汇总数据
     * @return array|string
     */
    public function getSummary($where = [])
    {
        if (!$where) {
            $this->initWhere($where);
        }

        $where['payment_status'] = 2;

        // 今天销售额
        $whereToday = $where;
        $whereToday[] = ['payment_time','>',strtotime(date("Y-m-d 00:00:00"))];
        $data['todaySale'] = $this->getSaleOrdersSum($whereToday,'total_price');

        //本月销售额
        $whereMonth = $where;
        $whereMonth[] = ['payment_time','>',strtotime(date("Y-m-01 00:00:00"))];
        $data['monthSale'] = $this->getSaleOrdersSum($whereMonth,'total_price');

        // 本年度销售额
        $whereYear = $where;
        $whereYear[] = ['payment_time','>',strtotime(date("Y-01-01 00:00:00"))];
        $data['yearSale'] = $this->getSaleOrdersSum($whereYear,'total_price');

        // 云值守销售额
        $whereUnattended = $where;
        $whereUnattended['order_type'] = 2;
        $data['unattendedSale'] = $this->getSaleOrdersSum($whereUnattended,'total_price');

        // 云仓销售额
        $whereWh = $where;
        $whereWh[] = ['order_type','between',['3','4']];
        $data['whSale'] = $this->getSaleOrdersSum($whereWh,'total_price');

        return $this->r(200,'汇总成功',$data);
    }

    /**
     * 利润汇总
     * @return array|string
     */
    public function getProfitSummary($where = [])
    {
        if (!$where) $this->initWhere($where);

        $where['payment_status'] = 2;
        $sum = "ifnull(sum((total_price - cost_price)),0) revenue";

        // 今天利润
        $whereToday = $where;
        $whereToday[] = ['payment_time','>',strtotime(date("Y-m-d 00:00:00"))];
        $data['todayProfit'] = $this->getSaleOrdersFind($whereToday,$sum)['revenue'];

        //本月利润
        $whereMonth = $where;
        $whereMonth[] = ['payment_time','>',strtotime(date("Y-m-01 00:00:00"))];
        $data['monthProfit'] = $this->getSaleOrdersFind($whereMonth,$sum)['revenue'];

        // 本年度利润
        $whereMonth = $where;
        $whereMonth[] = ['payment_time','>',strtotime(date("Y-m-01 00:00:00"))];
        $data['yearProfit'] = $this->getSaleOrdersFind($whereMonth,$sum)['revenue'];

        return $this->r(200,'利润汇总成功',$data);
    }

    /**
     * 门店销售排行榜
     * @param array $where
     * @param int $pageNum
     * @return array|string
     * @throws \Exception
     */
    public function storeSaleRankingList($where = [], $pageNum = 10)
    {
        if (!$where) {
            $this->initWhere($where);
            $date = $postData['create_time'] ?? date("Y-m-d",strtotime("-7 days")) . "~" . date("Y-m-d");
            if ($date) {
                $date = explode("~",$date);
                $where[] = ['create_time',"between",[strtotime($date[0]),strtotime($date[1])]];
            }
        }
        $where['payment_status'] = 2;
        $list = $this->getSaleOrdersList($where,$pageNum, 'store_id,store_name,sum(total_price) totalSale,sum(total_quantity) totalQuantity','totalSale desc','','store_id');
        return $this->rQ($list);
    }


    /**
     * 商品销售排行榜
     * @param int $pageNum
     * @return array|string
     */
    public function goodsSaleRankingList($where = [], $pageNum = 10)
    {
        if (!$where) {
            $this->initWhere($where);

            $date = $postData['create_time'] ?? date("Y-m-d",strtotime("-7 days")) . "~" . date("Y-m-d");
            if ($date) {
                $date = explode("~",$date);
                $where[] = ['create_time',"between",[strtotime($date[0]),strtotime($date[1])]];
            }
        }
        $where['payment_status'] = 2;
        $field = "goods_id,goods_name,sum(total_sod_price) totalSale,sum(total_quantity) totalQuantity";
        $group = 'goods_id';
        $order = 'totalSale desc';
        return $this->rQ($this->getSaleGoodsRankingList($where,$pageNum,$field,$order,$group));
    }

    /**
     * 云值守门店销售排行榜
     * @param array $where
     * @param int $pageNum
     * @return array|string
     * @throws \Exception
     */
    public function unattendedSaleList($where = [],$pageNum = 0)
    {
        if (!$where) {
            $this->initWhere($where);
            $date = $postData['create_time'] ?? date("Y-m-d",strtotime("-7 days")) . "~" . date("Y-m-d");
            if ($date) {
                $date = explode("~",$date);
                $where[] = ['create_time',"between",[strtotime($date[0]),strtotime($date[1])]];
            }
        }
        $where['payment_status'] = 2;
        $where['order_type'] = ['in',[2,5]];
        $group = 'store_id';
        $field = 'store_name,sum(total_price) totalSale,sum(total_quantity) totalQuantity';
        $order = 'totalSale desc';
        return $this->rQ($this->getSaleOrdersList($where,$pageNum,$field,$order,'',$group));

    }

    /**
     * 云仓门店销售排行榜
     * @param array $where
     * @param int $pageNum
     * @return array|string
     * @throws \Exception
     */
    public function cloudWhSaleList($where = [], $pageNum = 0 )
    {
        if (!$where) {
            $this->initWhere($where);
            $date = $postData['create_time'] ?? date("Y-m-d", strtotime("-7 days")) . "~" . date("Y-m-d");
            if ($date) {
                $date = explode("~", $date);
                $where[] = ['create_time', "between", [strtotime($date[0]), strtotime($date[1])]];
            }
        }
        $where['payment_status'] = 2;
        $where[] = ['order_type','between',[3,4]];
        $group = 'store_id';
        $field = 'store_name,sum(total_price) totalSale,sum(total_quantity) totalQuantity';
        $order = 'totalSale desc';
        return $this->rQ($this->getSaleOrdersList($where,$pageNum,$field,$order,'',$group));

    }

    /**
     * 获取销售折线图
     * @param $where
     * @return array|string
     * @throws \Exception
     */
    public function getBrokenLine($where = [])
    {
        if (!$where) {
            $this->initWhere($where);
            $date = $postData['create_time'] ?? date("Y-m-d", strtotime("-7 days")) . "~" . date("Y-m-d");
            if ($date) {
                $date = explode("~", $date);
                $where[] = ['create_time', "between", [strtotime($date[0]), strtotime($date[1])]];
            }
        }
        $where['payment_status'] = 2;
        $field = "FROM_UNIXTIME(create_time,'%Y-%m-%d') as date, sum(total_price) totalSale, sum(total_quantity) totalQuantity";
        $group = 'date';
        $order = 'date desc';
        return $this->rQ($this->getSaleOrdersList($where,0,$field,$order,'',$group));
    }

}