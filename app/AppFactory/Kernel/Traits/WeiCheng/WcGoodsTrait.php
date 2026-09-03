<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/01/05
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\WeiCheng;

use app\AppFactory\Kernel\Model\WeiCheng\WcGoodsModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcGoodsTypesModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcGoodsLocalModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcGoodsSyncLogModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcUserAddressesModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcMachineChannelModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcMachineGoodsModel;
use think\facade\Db;
use app\AppFactory\Kernel\Model\WeiCheng\WcMcSortLogModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcMcSortLogDetailModel;

trait WcGoodsTrait
{
    public function getWcGoodsColumn($where, $field = '*', $order = '')
    {
        return WcGoodsModel::getColumn($where, $field, $order);
    }

    public function getWcGoodsCount($where, $field = '*', $order = '')
    {
        return WcGoodsModel::getFind($where, $field, $order);
    }

    public function getWcGoodsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcGoodsModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcGoodsFind($where, $field = "*", $order = "")
    {
        return WcGoodsModel::getFind($where, $field, $order);
    }

    public function getWcGoodsSum($where, $sum)
    {
        return WcGoodsModel::getSum($where, $sum);
    }

    public function addWcGoods($insert)
    {
        $data = WcGoodsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateWcGoods($update, $where = [], $field = [])
    {
        return WcGoodsModel::update($update, $where, $field);
    }

    /**
     * 获取本次微程列表同步未返回的商品编码。
     */
    public function getWcGoodsMissingSyncOutNos($onlineStatus, $goodsType = 0)
    {
        $query = WcGoodsModel::where('id', '>', 0)
            ->whereRaw('(`sync_status` <> ? OR `sync_status` IS NULL)', [$onlineStatus]);
        if ($goodsType) {
            $query->where('type', '=', $goodsType);
        }
        return $query->column('no');
    }

    /**
     * 标记本次微程列表同步未返回的商品。
     */
    public function updateWcGoodsMissingSyncStatus($onlineStatus, $offlineStatus, $goodsType = 0)
    {
        $query = WcGoodsModel::where('id', '>', 0)
            ->whereRaw('(`sync_status` <> ? OR `sync_status` IS NULL)', [$onlineStatus]);
        if ($goodsType) {
            $query->where('type', '=', $goodsType);
        }
        return $query->update(['sync_status' => $offlineStatus]);
    }

    public function delWcGoods($where)
    {
        return WcGoodsModel::whereDel($where);
    }

    public function getWcGoodsTypesCount($where, $field = '*', $order = '')
    {
        return WcGoodsTypesModel::getFind($where, $field, $order);
    }

    public function getWcGoodsTypesList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcGoodsTypesModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcGoodsTypesFind($where, $field = "*", $order = "")
    {
        return WcGoodsTypesModel::getFind($where, $field, $order);
    }

    public function getWcGoodsTypesSum($where, $sum)
    {
        return WcGoodsTypesModel::getSum($where, $sum);
    }

    public function addWcGoodsTypes($insert)
    {
        $data = WcGoodsTypesModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateWcGoodsTypes($update, $where = [], $field = [])
    {
        return WcGoodsTypesModel::update($update, $where, $field);
    }

    public function delWcGoodsTypes($where)
    {
        return WcGoodsTypesModel::whereDel($where);
    }

    public function getWcGoodsLocalColumn($where, $column)
    {
        return WcGoodsLocalModel::getColumn($where, $column);
    }

    public function getWcGoodsLocalCount($where, $field = '*', $order = '')
    {
        return WcGoodsLocalModel::getFind($where, $field, $order);
    }

    public function getWcGoodsLocalList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcGoodsLocalModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcGoodsLocalFind($where, $field = "*", $order = "")
    {
        return WcGoodsLocalModel::getFind($where, $field, $order);
    }

    public function getWcGoodsLocalSum($where, $sum)
    {
        return WcGoodsLocalModel::getSum($where, $sum);
    }

    public function addWcGoodsLocal($insert)
    {
        $data = WcGoodsLocalModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateWcGoodsLocal($update, $where = [], $field = [])
    {
        return WcGoodsLocalModel::update($update, $where, $field);
    }

    public function delWcGoodsLocal($where)
    {
        return WcGoodsLocalModel::whereDel($where);
    }

    public function getWcMachineChannelList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcMachineChannelModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcMachineChannelFind($where, $field = "*", $order = "")
    {
        return WcMachineChannelModel::getFind($where, $field, $order);
    }

    public function getWcMachineChannelSum($where, $sum)
    {
        return WcMachineChannelModel::getSum($where, $sum);
    }

    public function addWcMachineChannel($insert)
    {
        $data = WcMachineChannelModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function addWcMachineChannelMore($insertAll)
    {
        $model = new WcMachineChannelModel();
        return $model->saveAll($insertAll);
    }

    public function updateWcMachineChannel($update, $where = [], $field = [])
    {
        return WcMachineChannelModel::update($update, $where, $field);
    }

    public function delWcMachineChannel($where)
    {
        return WcMachineChannelModel::whereDel($where);
    }

    /**
     * 删除指定微程商品在设备虚拟货道中的绑定。
     */
    public function deleteWcMachineChannelByOutNos(array $outNos)
    {
        if (!$outNos) return 0;
        return WcMachineChannelModel::where('out_no', 'in', $outNos)->delete();
    }

    /**
     * 物理删除微程未返回商品及其设备关联数据。
     */
    public function deleteWcGoodsDataByOutNos(array $outNos)
    {
        $outNos = array_values(array_filter(array_unique($outNos)));
        $summary = [
            'out_no_count' => count($outNos),
            'machine_count' => 0,
            'wc_machine_channel_deleted' => 0,
            'wc_machine_goods_deleted' => 0,
            'wc_goods_local_deleted' => 0,
            'wc_goods_deleted' => 0,
        ];
        if (!$outNos) return $summary;

        $machineIds = [];
        Db::startTrans();
        try {
            foreach (array_chunk($outNos, 500) as $chunk) {
                $machineIds = array_merge($machineIds, WcMachineGoodsModel::where('out_no', 'in', $chunk)->column('machine_id'));
                $summary['wc_machine_channel_deleted'] += WcMachineChannelModel::where('out_no', 'in', $chunk)->delete();
                $summary['wc_machine_goods_deleted'] += WcMachineGoodsModel::where('out_no', 'in', $chunk)->delete();
                $summary['wc_goods_local_deleted'] += WcGoodsLocalModel::where('out_no', 'in', $chunk)->delete();
                $summary['wc_goods_deleted'] += WcGoodsModel::where('no', 'in', $chunk)->delete();
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        $summary['machine_count'] = count(array_filter(array_unique($machineIds)));
        return $summary;
    }

    /**
     * 删除一个微程父商品详情中已经不存在的旧子商品。
     */
    public function deleteWcGoodsLocalMissingNos($outNo, array $currentNos)
    {
        $query = WcGoodsLocalModel::where('out_no', '=', $outNo);
        $currentNos = array_values(array_filter(array_unique($currentNos)));
        if ($currentNos) $query->where('no', 'not in', $currentNos);
        return $query->delete();
    }

    // ========== wc_goods_sync_logs 商品同步日志 ==========

    /**
     * 写入商品同步日志（保存商品详情接口原始返回）。
     */
    public function addWcGoodsSyncLog($goodsNo, $goodsType, $syncBatchNo, $getData)
    {
        return WcGoodsSyncLogModel::create([
            'goods_no'      => $goodsNo,
            'goods_type'    => intval($goodsType),
            'sync_batch_no' => $syncBatchNo,
            'get_data'      => $getData,
        ]);
    }

    /**
     * 获取某商品最新一条同步日志（含 get_data）。
     */
    public function getWcGoodsLatestSyncLog($goodsNo)
    {
        return WcGoodsSyncLogModel::where('goods_no', '=', $goodsNo)
            ->order('id', 'desc')
            ->find();
    }

    /**
     * 清理指定时间点之前的同步日志，返回删除条数。
     */
    public function deleteWcGoodsSyncLogBefore($deadline)
    {
        return WcGoodsSyncLogModel::where('created_at', '<', $deadline)->delete();
    }

    public function getWcMachineGoodsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcMachineGoodsModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcMachineGoodsFind($where, $field = "*", $order = "")
    {
        return WcMachineGoodsModel::getFind($where, $field, $order);
    }

    public function getWcMachineGoodsSum($where, $sum)
    {
        return WcMachineGoodsModel::getSum($where, $sum);
    }

    public function addWcMachineGoods($insert)
    {
        $data = WcMachineGoodsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function addWcMachineGoodsMore($insertAll)
    {
        $model = new WcMachineGoodsModel();
        return $model->saveAll($insertAll);
    }

    public function updateWcMachineGoods($update, $where = [], $field = [])
    {
        return WcMachineGoodsModel::update($update, $where, $field);
    }

    /**
     * 将指定微程商品在设备上的在线绑定标记为下架。
     */
    public function offShelfWcMachineGoodsByOutNos(array $outNos)
    {
        if (!$outNos) return 0;
        return WcMachineGoodsModel::where('is_shelf', '=', 1)
            ->where('out_no', 'in', $outNos)
            ->update(['is_shelf' => 2]);
    }

    public function delWcMachineGoods($where)
    {
        return WcMachineGoodsModel::whereDel($where);
    }

    public function getWcUserAddressesList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcUserAddressesModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcUserAddressesFind($where, $field = "*", $order = "")
    {
        return WcUserAddressesModel::getFind($where, $field, $order);
    }

    public function addWcUserAddresses($insert)
    {
        $data = WcUserAddressesModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function addWcUserAddressesMore($insertAll)
    {
        $model = new WcUserAddressesModel();
        return $model->saveAll($insertAll);
    }

    public function updateWcUserAddresses($update, $where = [], $field = [])
    {
        return WcUserAddressesModel::update($update, $where, $field);
    }

    public function delWcUserAddresses($where)
    {
        return WcUserAddressesModel::whereDel($where);
    }

    // ========== wc_mc_sort_log ==========

    public function getWcMcSortLogList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcMcSortLogModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcMcSortLogFind($where, $field = "*", $order = "")
    {
        return WcMcSortLogModel::getFind($where, $field, $order);
    }

    public function addWcMcSortLog($insert)
    {
        $data = WcMcSortLogModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    // ========== wc_mc_sort_log_detail ==========

    public function getWcMcSortLogDetailList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcMcSortLogDetailModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function addWcMcSortLogDetailMore($insertAll)
    {
        $model = new WcMcSortLogDetailModel();
        return $model->saveAll($insertAll);
    }

    public function test(){
        //测试解决冲突
    }   
        
}
