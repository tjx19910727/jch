<?php

namespace app\management\controller\warehouse;

use app\management\controller\Common;
use app\management\validate\Warehouse\VWarehouseTrans;

class WarehouseTrans extends Common
{
    /**
     * 创建即时生效的仓库库存变化单。
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, VWarehouseTrans::class . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->warehouseTrans->createTrans($postData);
    }

    public function getList()
    {
        $postData = input();
        $pageNum = intval($postData['pageNum'] ?? 0);
        $where = $this->getWhere($postData, false, [
            'trans_id' => 'like',
            'record_no' => 'like',
            'business_at' => 'between',
            'created_at' => 'between',
        ]);
        return $this->app->warehouseTrans->getTransList($where, $pageNum);
    }

    public function getFind()
    {
        $postData = input();
        if (empty($postData['id']) && empty($postData['trans_id'])) {
            return returnState(100, '仓库变化记录ID或单号不能为空');
        }
        $where = [];
        if (!empty($postData['id'])) $where[] = ['id', '=', intval($postData['id'])];
        if (!empty($postData['trans_id'])) $where[] = ['trans_id', '=', trim(strval($postData['trans_id']))];
        $where = $this->authNodeWhere($where);
        return $this->app->warehouseTrans->getTransFind($where);
    }

    /**
     * 导出仓库变化单的商品明细。
     */
    public function exportTransDetails()
    {
        $postData = input();
        if (empty($postData['id']) && empty($postData['trans_id'])) {
            return returnState(100, '仓库变化记录ID或单号不能为空');
        }
        $where = [];
        if (!empty($postData['id'])) $where[] = ['id', '=', intval($postData['id'])];
        if (!empty($postData['trans_id'])) $where[] = ['trans_id', '=', trim(strval($postData['trans_id']))];
        $where = $this->authNodeWhere($where);
        return $this->app->warehouseTrans->exportTransDetails($where);
    }

    public function getPreReplenishmentGoodsList()
    {
        $postData = input();
        try {
            $this->validate($postData, VWarehouseTrans::class . '.getPreReplenishmentGoodsList');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->warehouseTrans->getPreReplenishmentGoodsList(trim(strval($postData['record_no'])));
    }
}
