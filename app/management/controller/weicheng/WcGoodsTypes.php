<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/5
 * Time: 08:50
 */

namespace app\management\controller\weicheng;

use app\AppFactory\AppFactory;
use app\management\controller\Common;

class WcGoodsTypes extends Common
{
    public function sync()
    {
        $goods_type = input('goods_type');
        if (!$goods_type) {
            return $this->app->weicheng->synchronizeGoodsTypesAll();
        }
        return $this->app->weicheng->synchronizeGoodsTypes($goods_type);
    }

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['name' => 'like']);
        return $this->app->weicheng->getWcGoodsTypesInfoList($where, $pageNum, "*", 'id desc');
    }
}
