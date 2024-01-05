<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 17:05
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Auth\AuthManagerModel;
use app\AppFactory\Kernel\Model\Store\StoreShelvesRepModel;

trait StoreShelvesRepTrait
{
    public function getStoreShelvesRepFind($where,$field = "*", $order = "rep_id desc")
    {
        return StoreShelvesRepModel::getFind($where,$field,$order);
    }

    /**
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return StoreShelvesRepModel|StoreShelvesRepModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getStoreShelvesRepList($where,$pageNum = 0, $field = "*", $order = "rep_id desc")
    {
        return StoreShelvesRepModel::getList($where, $pageNum, $field, $order)->each(function ($item) {
            $item['nickname'] = AuthManagerModel::getFieldValue(['manager_id' => $item['manager_id']],'nickname');
            return $item;
        });
    }

    public function addStoreShelvesRep($insert)
    {
        $insert["manager_id"] = $this->manager['manager_id'];
        $rep = StoreShelvesRepModel::create($insert);
        return $rep->rep_id;
    }
}