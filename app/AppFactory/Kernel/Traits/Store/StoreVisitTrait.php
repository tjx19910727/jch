<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/29
 * Time: 9:40
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreVisitModel;

trait StoreVisitTrait
{
    public function getStoreVisitFind($where,$field = "*",$order = "")
    {
        $data = StoreVisitModel::getFind($where,$field,$order);
        return $data;
    }

    /**
     * 查询门店到访记录表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return StoreVisitModel|StoreVisitModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getStoreVisitList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return StoreVisitModel::getList($where,$pageNum,$field,$order);
    }

    public function addStoreVisit($insert)
    {
        $visit = StoreVisitModel::create($insert);
        return $visit->visit_id;
    }

    public function updateStoreVisit($update,$where = [],$field = [])
    {
        return StoreVisitModel::update($update,$where,$field);
    }

}