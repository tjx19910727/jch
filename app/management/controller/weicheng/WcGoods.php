<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/5
 * Time: 08:50
 */

namespace app\management\controller\weicheng;

use app\AppFactory\AppFactory;
use app\AppFactory\RabbitMq\MqProducer;
use app\management\controller\Common;
use think\facade\Cache;

class WcGoods extends Common
{

    public function syncAll()
    {
        $cacheKey = 'wc_goods_sync_all_lock';
        if (Cache::get($cacheKey)) return returnState(100, '5分钟内只能请求一次，请稍后重试');

        $goods_no = input('goods_no') ?? '';
        $type = input('type');
        $res = MqProducer::export([
            'job_type' => 'wc_goods_sync',
            'request_time' => date('Y-m-d H:i:s'),
            'manager_id' => input('manager_id') ?? 0,
            'goods_no' => $goods_no,
            'type' => $type,
        ]);
        if ($res != 'OK') return returnState(100, '同步请求提交失败：' . $res);
        Cache::set($cacheKey, 1, 300);
        return returnState(200, 'success', '同步请求已提交，请5分钟后再刷新页面');
    }

    public function sync()
    {
        $goods_no = input('goods_no');
        $type = input('type');
        $res = $this->app->weicheng->synchronizeGoods($goods_no, $type);
        if ($res['status']) return returnState(200, 'success', '同步成功');
        return returnState(100, $res['msg']);
    }


    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['type' => 'like', 'name' => 'like', 'no' => 'like']);
        return $this->app->weicheng->getWcGoodsInfoList($where, $pageNum, "*", 'id desc');
    }

    public function setSingleWcGoodsLocal()
    {
        $goods_no = input('goods_no') ?? '';
        if (!$goods_no) return returnState(100, 'goods_no不能为空');
        $wc_goods = $this->app->weicheng->getWcGoodsFind(['no' => $goods_no]);
        $res = $this->app->weicheng->setWcGoodsLocal($goods_no, $wc_goods['type']);
        if ($res) return returnState(200, 'success', '本地化处理成功');
        return returnState(100, 'fail', '本地化处理失败');
    }

    public function setWcGoodsLocal()
    {
        return $this->app->weicheng->wcGoodsWriteLocal(input('goods_no'));
    }

    public function getWcPhysicalGoodsLists()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        // 这里不再透传 type like，避免与实物类型固定筛选冲突导致结果为空
        $where = $this->getWhere($postData, false, ['name' => 'like', 'no' => 'like']);
        $where[] = ['type', 'in', [1, 2, 3, 4, 5]]; // 实物商品
        return $this->app->weicheng->getWcPhysicalGoodsLists($where, $pageNum);
    }

    public function getWcCombinGoodsLists()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['type' => 'like', 'name' => 'like', 'no' => 'like']);
        $where['type'] = 11; //组合商品
        return $this->app->weicheng->getWcCombinGoodsLists($where, $pageNum, "*", 'id desc');
    }

    //多对多微程商品与设备绑定
    public function setWcMachineGoodsLists()
    {
        $postData = input();
        $m_ids = $postData['m_ids'] ?? 0;
        $out_nos = $postData['out_nos'] ?? [];
        $m_ids = is_array($m_ids) ? $m_ids : explode(',', $m_ids);
        $out_nos = is_array($out_nos) ? $out_nos : explode(',', $out_nos);
        return $this->app->weicheng->setWcMachineGoodsBatchLists($m_ids, $out_nos);
    }

    public function getWcMachineGoodsLists()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['machine_id' => 'like']);
        return $this->app->weicheng->getWcMachineGoodsLists($where, $pageNum, "*", 'id desc');
    }

    public function delWcMachineGoods()
    {
        $postData = input();
        $out_nos = $postData['out_nos'] ?? [];
        if (!$out_nos) return  returnState(100, 'out_nos不能为空');
        $out_nos = is_array($out_nos) ? $out_nos : explode(',', $out_nos);
        $where[] = ['machine_id', '=', $postData['machine_id']];
        $where[] = ['out_no', 'in', $out_nos];
        return $this->app->weicheng->delWcMG($where);
    }
}
