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
     * 销售汇总
     * @return array|string
     */
    public function salesSummary()
    {
        $where = $this->getWhere([]);
        return $this->app->saleData->getSummary($where);
    }

    /**
     * 利润汇总
     * @return array|string
     */
    public function profitSummary()
    {
        $where = $this->getWhere([]);
        return $this->app->saleData->getProfitSummary($where);
    }

    /**
     * 门店销售排行榜
     * @return array|string
     * @throws \Exception
     */
    public function storeSaleList()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->saleData->storeSaleRankingList($where);
    }

    /**
     * 商品销售排行榜
     * @return array|string
     */
    public function goodsSaleList()
    {
        $pageNum = input('pageNum', 10);
        $where = $this->getWhere([]);
        return $this->app->saleData->goodsSaleRankingList($where, $pageNum);
    }

    /**
     * 云值守销售排行榜
     * @return array|string
     * @throws \Exception
     */
    public function unattendedSaleList()
    {
        $pageNum = input('pageNum', 10);
        $where = $this->getWhere([]);
        return $this->app->saleData->unattendedSaleList($where, $pageNum);
    }

    /**
     * 云仓销售排行榜
     * @return array|string
     * @throws \Exception
     */
    public function cloudWhSaleList()
    {
        $pageNum = input('pageNum', 10);
        $where = $this->getWhere([]);
        return $this->app->saleData->unattendedSaleList($where, $pageNum);
    }

    /**
     * 获取销售折线图
     * @return array|string
     * @throws \Exception
     */
    public function getBrokenLine()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->saleData->getBrokenLine($where);
    }

    /**
     * 门店汇总
     * @return array|string
     */
    public function storeSummary()
    {
        $where = $this->getWhere([]);
        return $this->app->storeData->getSummary($where);
    }

    /**
     * 货损汇总
     * @return array|string
     */
    public function cargoDamageSummary()
    {
        $where = $this->getWhere([]);
        return $this->app->cargoDamageData->getSummary($where);
    }

    /**
     * 获取待办事项列表
     * @return array|string
     * @throws \Exception
     */
    public function getTodoList()
    {
        $pageNum = input('pageNum', 0);
        return $this->app->todo->getFieldList($pageNum);
    }

    /**
     * 忽略待办事项
     * @return mixed
     */
    public function ignoreTodo()
    {
        $id = input('id');
        return returnData($this->app->todo->updateStatus($id));
    }

    public function addTodo()
    {
        $postData = input();

    }


}