<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/05
 * Time: 15:42
 */

namespace app\AppFactory\Management\WeiCheng;

use app\AppFactory\Kernel\Traits\WeiCheng\WcBaseTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;
use app\AppFactory\RabbitMq\AsyncTask\WcGoodsSyncLock;

class WeiChengClient extends ManagementClient
{
    use WcBaseTrait, WcGoodsTrait, MachineTrait, MachineGoodsTrait;

    public function getWcGoodsInfoColumn($where, $field = "*")
    {
        return $this->rQ($this->getWcGoodsColumn($where, $field));
    }

    public function getWcGoodsInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcGoodsList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcGoodsInfo($postData)
    {
        return $this->rA($this->addWcGoods($postData));
    }

    public function updateWcGoodsInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcGoods($update, $where, $field));
    }

    public function delWcGoodsInfo($where)
    {
        return $this->rD($this->delWcGoods($where));
    }

    public function getWcGoodsTypesInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcGoodsTypesList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcGoodsTypesInfo($postData)
    {
        return $this->rA($this->addWcGoodsTypes($postData));
    }

    public function updateWcGoodsTypesInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcGoodsTypes($update, $where, $field));
    }

    public function delWcGoodsTypesInfo($where)
    {
        return $this->rD($this->delWcGoodsTypes($where));
    }

    public function getWcRequestLogsInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcRequestLogsList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcRequestLogsInfo($postData)
    {
        return $this->rA($this->addWcRequestLogs($postData));
    }

    public function updateWcRequestLogsInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcRequestLogs($update, $where, $field));
    }

    public function delWcRequestLogsInfo($where)
    {
        return $this->rD($this->delWcRequestLogs($where));
    }

    public function getWcGoodsLocalInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcGoodsLocalList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcGoodsLocalInfo($postData)
    {
        return $this->rA($this->addWcGoodsLocal($postData));
    }

    public function updateWcGoodsLocalInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcGoodsLocal($update, $where, $field));
    }

    public function delWcGoodsLocalInfo($where)
    {
        return $this->rD($this->delWcGoodsLocal($where));
    }

    public function getWcMachineChannelInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcMachineChannelList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcMachineChannelInfo($postData)
    {
        return $this->rA($this->addWcMachineChannel($postData));
    }

    public function updateWcMachineChannelInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcMachineChannel($update, $where, $field));
    }

    public function delWcMachineChannelInfo($where)
    {
        return $this->rD($this->delWcMachineChannel($where));
    }


    public function getWcUserAddressesInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcUserAddressesList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcUserAddressesInfo($postData)
    {
        return $this->rA($this->addWcUserAddresses($postData));
    }

    public function updateWcUserAddressesInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcUserAddresses($update, $where, $field));
    }

    public function delWcUserAddressesInfo($where)
    {
        return $this->rD($this->delWcUserAddresses($where));
    }


    public function synchronizeGoodsTypesAll($syncBatchNo = '')
    {
        if ($syncBatchNo === '') $syncBatchNo = date('YmdHis');
        $wc_goods_type = $this->getWcGoodsTypesList([['id', '>', '0']]);
        if (!$wc_goods_type) return true;
        $wc_goods_type = $wc_goods_type->toArray();
        $results = [];
        foreach ($wc_goods_type as $type) {
            $res = $this->app->weicheng->synchronizeGoodsTypes($type['id'], 1, $syncBatchNo, false);
            $results[] = $res;
            if (!$this->isWcSyncSuccess($res)) {
                actionLog(['goods_type' => $type['id'], 'result' => $this->normalizeWcSyncLog($res), 'sync_batch_no' => $syncBatchNo], '微程分类同步失败，跳过未返回标记', "export_message_sync");
                return $res;
            }
        }
        $this->markWcGoodsMissingFromSync($syncBatchNo);
        return $this->r(200, '分类商品同步成功', [
            'sync_batch_no' => $syncBatchNo,
            'result' => $results,
        ]);
    }

    public function synchronizeGoodsTypes($goods_type, $nowPage = 1, $syncBatchNo = '', $autoFinalize = true)
    {
        if ($syncBatchNo === '') $syncBatchNo = date('YmdHis');
        $result = $this->goodsTypesSync($goods_type, $nowPage);
        if ($result['status'] != 200) {
            return $this->r(100, '分类商品同步失败: ' . $result['response']);
        }
        $updateData = json2arr($result['response']);
        if (!$updateData || !isset($updateData['data'])) {
            return $this->r(100, '分类商品同步失败: 微程返回格式异常');
        }

        $totalPage = isset($updateData['data']['totalPage']) ? intval($updateData['data']['totalPage']) : 1;
        $goods_lists = $updateData['data']['list'] ?? [];

        $res = $this->synchronizeGoodsLists2Db($goods_lists, $goods_type, $syncBatchNo);

        // 如果还有下一页，递归处理并合并结果
        if ($nowPage < $totalPage) {
            $nextRes = $this->synchronizeGoodsTypes($goods_type, $nowPage + 1, $syncBatchNo, false);
            if (!$this->isWcSyncSuccess($nextRes)) return $nextRes;

            // 合并当前页与后续页的结果，尽量兼容各种返回类型
            $current = is_array($res) ? $res : [$res];
            if (is_array($nextRes)) {
                $combined = array_merge($current, $nextRes);
            } else {
                $current[] = $nextRes;
                $combined = $current;
            }
        } else {
            $combined = is_array($res) ? $res : [$res];
        }

        // 仅在顶层调用时返回标准化响应，递归内部返回原始合并结果
        if ($nowPage === 1) {
            if ($autoFinalize) $this->markWcGoodsMissingFromSync($syncBatchNo, $goods_type);
            return $this->r(200, '分类商品同步成功', [
                'sync_batch_no' => $syncBatchNo,
                'goods_type' => $goods_type,
                'result' => $combined,
            ]);
        }

        return $combined;
    }

    public function synchronizeGoodsAll($syncBatchNo = '', $taskId = '')
    {
        $startTime = microtime(true);
        if ($syncBatchNo === '') $syncBatchNo = date('YmdHis');
        $wc_goods = $this->getWcGoodsList([['id', '>', '0'], ['is_pub', '=', '1']])->toArray();
        $successCount = 0;
        $failureCount = 0;
        $failureGoodsNos = [];
        $qrcodeFailureCount = 0;
        $qrcodeFailureGoodsNos = [];
        foreach ($wc_goods as $index => $v) {
            if ($taskId !== '' && $index % 20 === 0) WcGoodsSyncLock::refresh($taskId);
            $res = $this->synchronizeGoods($v['no'], $v['type'], $syncBatchNo);
            if (empty($res['status'])) {
                $failureCount++;
                if (count($failureGoodsNos) < 100) $failureGoodsNos[] = $v['no'];
                continue;
            }
            if (isset($res['qrcode_status']) && !$res['qrcode_status']) {
                $qrcodeFailureCount++;
                if (count($qrcodeFailureGoodsNos) < 100) $qrcodeFailureGoodsNos[] = $v['no'];
            }
            $successCount++;
        }
        if ($taskId !== '') WcGoodsSyncLock::refresh($taskId);
        $summary = [
            'sync_batch_no' => $syncBatchNo,
            'total_count' => count($wc_goods),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failure_goods_nos' => $failureGoodsNos,
            'failure_goods_nos_truncated' => $failureCount > count($failureGoodsNos) ? 1 : 0,
            'duration_ms' => intval((microtime(true) - $startTime) * 1000),
            'qrcode_failure_count' => $qrcodeFailureCount,
            'qrcode_failure_goods_nos' => $qrcodeFailureGoodsNos,
            'qrcode_failure_goods_nos_truncated' => $qrcodeFailureCount > count($qrcodeFailureGoodsNos) ? 1 : 0,
        ];
        actionLog($summary, '微程商品详情同步汇总', 'async_task_wc_goods_sync');
        $state = $failureCount > 0 ? 100 : 200;
        $message = $failureCount > 0
            ? '微程商品详情同步存在失败'
            : ($qrcodeFailureCount > 0 ? '微程商品基础信息同步成功，部分二维码同步失败' : '微程商品详情同步成功');
        return returnState($state, $message, $summary);
    }

    public function synchronizeGoods($goods_no, $type, $syncBatchNo = '')
    {
        if ($syncBatchNo === '') $syncBatchNo = date('YmdHis');
        $result = $this->goodsSync($goods_no, $type);
        if ($result['status'] != 200) return ['status' => false, 'msg' => $result['response']];

        $res = json2arr($result['response']);
        if (!$res || !isset($res['product']) || !is_array($res['product'])) {
            return ['status' => false, 'msg' => '微程返回商品详情格式异常'];
        }

        $updateData = $this->mergeAppointmentGoodsDaysInfo($res['product']);
        $updateData['get_data'] = $result['response'];
        $updateData['goods'] = json_encode(isset($updateData['goods']) && is_array($updateData['goods']) ? $updateData['goods'] : [], JSON_UNESCAPED_UNICODE);
        $updateData['combination_goods'] = json_encode(isset($updateData['combination_goods']) && is_array($updateData['combination_goods']) ? $updateData['combination_goods'] : [], JSON_UNESCAPED_UNICODE);
        $updateData['resourcesArray'] = json_encode(isset($updateData['resourcesArray']) && is_array($updateData['resourcesArray']) ? $updateData['resourcesArray'] : [], JSON_UNESCAPED_UNICODE);
        $updateData['daysInfo'] = json_encode(isset($updateData['daysInfo']) && is_array($updateData['daysInfo']) ? $updateData['daysInfo'] : [], JSON_UNESCAPED_UNICODE);
        if (isset($updateData['present_integral'])) $updateData['gift_points'] = $updateData['present_integral'] ?? 0;
        if (isset($updateData['type'])) unset($updateData['type']);

        Db::startTrans();
        try {
            $this->synchronizeGoods2Db($updateData, $syncBatchNo);
            $this->setWcGoodsLocal($goods_no, $type);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            actionException($e, 1);
            return ['status' => false, 'msg' => $e->getMessage()];
        }

        try {
            $qrcodeResult = $this->synchronizeGoodsQrCode($goods_no);
            if (!$qrcodeResult['status']) {
                actionLog([
                    'goods_no' => $goods_no,
                    'msg' => $qrcodeResult['msg'] ?? '',
                ], '微程商品二维码同步失败，基础信息已保留', 'async_task_wc_goods_sync');
                return ['status' => true, 'qrcode_status' => false, 'qrcode_msg' => $qrcodeResult['msg'] ?? ''];
            }
            return ['status' => true, 'qrcode_status' => true];
        } catch (\Throwable $e) {
            actionException($e, 1);
            actionLog([
                'goods_no' => $goods_no,
                'msg' => $e->getMessage(),
            ], '微程商品二维码同步异常，基础信息已保留', 'async_task_wc_goods_sync');
            return ['status' => true, 'qrcode_status' => false, 'qrcode_msg' => $e->getMessage()];
        }
    }


    /**
     * 商品详情落库后同步父商品二维码，并仅向唯一父商品映射的实物商品回填二维码。
     */
    protected function synchronizeGoodsQrCode($goodsNo)
    {
        $qrcodeUrl = $this->getWcGoodsQrCode($goodsNo);
        if ($qrcodeUrl === '') {
            return ['status' => false, 'msg' => '微程商品小程序码同步失败：' . $goodsNo];
        }

        $gIds = $this->getWcGoodsLocalColumn(['out_no' => $goodsNo], 'g_id');
        $gIds = array_values(array_unique(array_filter(array_map('intval', is_array($gIds) ? $gIds : []), function ($gId) {
            return $gId > 0 && $gId !== 9999;
        })));
        if (!$gIds) return ['status' => true];

        $mappingRows = Db::name('wc_goods_local')
            ->where('g_id', 'in', $gIds)
            ->field('g_id,COUNT(DISTINCT out_no) out_no_count')
            ->group('g_id')
            ->select()
            ->toArray();
        $uniqueGIds = [];
        $ambiguousGIds = [];
        foreach ($mappingRows as $mappingRow) {
            $gId = intval($mappingRow['g_id'] ?? 0);
            if (intval($mappingRow['out_no_count'] ?? 0) === 1) {
                $uniqueGIds[] = $gId;
            } else {
                $ambiguousGIds[] = $gId;
            }
        }

        if ($uniqueGIds) {
            Db::name('goods')->where('g_id', 'in', $uniqueGIds)->update(['goods_qrcode' => $qrcodeUrl]);
        }
        if ($ambiguousGIds) {
            Db::name('goods')->where('g_id', 'in', $ambiguousGIds)->update(['goods_qrcode' => '']);
            actionLog([
                'out_no' => $goodsNo,
                'g_ids' => $ambiguousGIds,
            ], '微程商品二维码存在一对多映射，已跳过普通商品回填', 'async_task_wc_goods_sync');
        }
        return ['status' => true];
    }
    /**
     * 预约商品明细位于 appointment.goods，合并到商品快照供父表和本地表复用。
     */
    protected function mergeAppointmentGoodsDaysInfo($product)
    {
        if (!is_array($product)) return $product;

        $appointmentGoods = isset($product['appointment']['goods']) && is_array($product['appointment']['goods'])
            ? $product['appointment']['goods']
            : [];
        unset($product['appointment']);
        if (!$appointmentGoods) return $product;

        $goods = isset($product['goods']) && is_array($product['goods']) ? $product['goods'] : [];
        $goodsIndex = [];
        foreach ($goods as $index => $good) {
            if (!empty($good['no'])) $goodsIndex[$good['no']] = $index;
        }

        foreach ($appointmentGoods as $appointmentGood) {
            if (!is_array($appointmentGood) || intval($appointmentGood['type'] ?? 0) !== 1) continue;
            if (!isset($appointmentGood['daysInfo']) || !is_array($appointmentGood['daysInfo'])) continue;

            if (!isset($product['daysInfo'])) $product['daysInfo'] = $appointmentGood['daysInfo'];
            $goodsNo = trim(strval($appointmentGood['no'] ?? ''));
            if ($goodsNo !== '' && isset($goodsIndex[$goodsNo])) {
                $goods[$goodsIndex[$goodsNo]] = array_merge($goods[$goodsIndex[$goodsNo]], $appointmentGood);
                continue;
            }
            $goods[] = $appointmentGood;
            if ($goodsNo !== '') $goodsIndex[$goodsNo] = count($goods) - 1;
        }

        if ($goods) $product['goods'] = $goods;
        return $product;
    }

    protected function markWcGoodsMissingFromSync($syncBatchNo, $goodsType = 0)
    {
        $onlineStatus = $syncBatchNo . '_1';
        $missingOutNos = array_values(array_filter(array_unique($this->getWcGoodsMissingSyncOutNos($onlineStatus, $goodsType))));
        $summary = $this->deleteWcGoodsDataByOutNos($missingOutNos);
        $summary['sync_batch_no'] = $syncBatchNo;
        $summary['goods_type'] = $goodsType;
        actionLog($summary, '微程商品未返回物理删除结果', 'export_message_sync');
        return $summary;
    }

    protected function isWcSyncSuccess($result)
    {
        if ($result === true) return true;
        if (is_array($result)) {
            if (isset($result['status']) && $result['status'] === false) return false;
            if (isset($result['state']) && intval($result['state']) !== 200) return false;
            return true;
        }
        if (is_object($result) && method_exists($result, 'getData')) {
            $data = $result->getData();
            if (is_object($data)) $data = json_decode(json_encode($data), true);
            return is_array($data) && intval($data['state'] ?? 200) === 200;
        }
        if (is_object($result) && method_exists($result, 'getContent')) {
            $data = json2arr($result->getContent());
            return is_array($data) && intval($data['state'] ?? 200) === 200;
        }
        return true;
    }

    protected function normalizeWcSyncLog($result)
    {
        if (is_object($result) && method_exists($result, 'getData')) return $result->getData();
        if (is_object($result) && method_exists($result, 'getContent')) return json2arr($result->getContent());
        return $result;
    }

    public function wcGoodsWriteLocal()
    {
        $wc_goods = $this->getWcGoodsList([['id', '>', '0'],['is_pub', '=', '1']])->toArray();
        // $wc_goods = $this->getWcGoodsList(['no'=>'VC2601071001'])->toArray();
        foreach ($wc_goods as $wc_good) {
            $res = $this->setWcGoodsLocal($wc_good['no'], $wc_good['type']);
        }
        return $this->rA('微程商品本地化写入完成');
    }

    //获取设备可排序的微程商品列表
    public function getWcPhysicalGoodsLists($where, $pageNum)
    {
        $wc_goods_local = $this->getWcGoodsLocalList($where, $pageNum, '*', 'id desc')->toArray();
        return  $this->rQ($wc_goods_local);
    }

    //获取设备可排序的微程商品列表
    public function getWcCombinGoodsLists($where, $pageNum)
    {
        $list  = $this->getWcGoodsList($where, $pageNum, '*', 'id desc');
        foreach ($list as &$v) {
            $v['goods_list'] = $this->getWcGoodsLocalList(['out_no' => $v['no']])->toArray();
        }
        return  $this->rQ($list);
    }

    public function setWcMachineGoodsBatchLists($m_ids, $out_nos)
    {
        $flag = [];
        foreach ($m_ids as $m_id) {
            $machine = $this->getMachineFind(['m_id' => $m_id]);
            if (!$machine) continue;
            $machine = $machine->toArray();
            $res = $this->setWcMachineGoodsLists($m_id, $machine['machine_id'], $out_nos);
            $flag[] = $res;
        }

        if ($this->checkFlag($flag)) return $this->rA('微程商品与设备绑定完成');
        return $this->rA('绑定失败');
    }


    public function setWcMachineGoodsLists($m_id, $machine_id, $out_nos)
    {
        // $this->delWcMachineGoods(['machine_id' => $machine_id]);
        $wc_goods_type = $this->getWcGoodsTypesList([['id', '>', '0']])->toArray();
        $wc_goods_type_arr = [];
        foreach ($wc_goods_type as $v) {
            $wc_goods_type_arr[$v['id']] = $v['name'];
        }

        foreach ($out_nos as $v) {
            $wc_goods = $this->getWcGoodsFind(['no' => $v]);
            if (!$wc_goods) continue;
            $wc_goods = $wc_goods->toArray();
            $resourcesArray = $wc_goods['resourcesArray'] ? json_decode($wc_goods['resourcesArray'], true) : [];
            $pic = '';
            if (isset($resourcesArray[0]['url'])) $pic = $wc_goods['resourceDomain'] . $resourcesArray[0]['url'];
            //添加一步处理，判断wc_goods类型，如果是5，则说明g_id有值，此时去wc_goods_local中找到它的g_id
            if ($wc_goods['type'] == 5) {
                $wc_goods_local = $this->getWcGoodsLocalFind(['out_no' => $wc_goods['no']]);
                if ($wc_goods_local) {
                    $wc_goods['g_id'] = $wc_goods_local['g_id'] ?? '';
                }
            }
            $inserData = [
                'm_id' => $m_id,
                'machine_id' => $machine_id,
                'out_no' => $wc_goods['no'],
                'g_id' => $wc_goods['g_id'] ?? '',
                'g_name' => $wc_goods['name'],
                'type' => $wc_goods['type'], //  这里传的type应该不是外层type  所以type_name未知
                'type_name' => $wc_goods_type_arr[$wc_goods['type']] ?? '',
                'pic' => $pic,
                'sku' => $wc_goods['sku'] ?? '',
                'bar_code' => $wc_goods['sku'] ?? '',
                'retail_price' => $wc_goods['price'] ?? 0,
                'gift_points' => $wc_goods['gift_points'] ?? 0,
                'sort' => array_search($wc_goods['no'], $out_nos) + 1,
            ];
            $wc_machine_goods = $this->getWcMachineGoodsFind(['m_id' => $m_id, 'machine_id' => $machine_id, 'out_no' => $wc_goods['no']]);
            if ($wc_machine_goods) $flag[] = $this->updateWcMachineGoods($inserData, ['id' => $wc_machine_goods['id']]);
            else $flag[] = $this->addWcMachineGoods($inserData);
        }
        return true;
    }


    public function getWcMachineGoodsLists($where, $pageNum = 0)
    {
        $list  = $this->getWcMachineGoodsList($where, $pageNum, '*', 'sort asc');
        foreach ($list as &$v) {
            $v['goods_list'] = $this->getWcGoodsLocalList(['out_no' => $v['out_no']])->toArray();
        }
        return  $this->rQ($list);
    }

    //删除设备绑定的微程商品
    public function delWcMG($where)
    {
        return $this->rD($this->delWcMachineGoods($where));
    }

    //设置虚拟货道商品排序
    public function setWcMachineChannelLists($m_id, $out_nos)
    {
        $machine = $this->getMachineFind(['m_id' => $m_id])->toArray();
        //删除历史记录，重新新增当前排序记录
        $res = $this->delWcMachineChannelInfo(['m_id' => $m_id]);
        $wc_machine_goods_lists = $this->getWcMachineGoodsList([['out_no', 'in', $out_nos], ['m_id', '=', $m_id]])->toArray();
        if (empty($wc_machine_goods_lists)) return $this->r(100, '上架失败，找不到微程商品信息');

        $wc_goods_type = $this->getWcGoodsTypesList([['id', '>', '0']])->toArray();
        $wc_goods_type_arr = [];
        foreach ($wc_goods_type as $v) {
            $wc_goods_type_arr[$v['id']] = $v['name'];
        }
        $inserData = [];
        $flag = [];
        foreach ($wc_machine_goods_lists as $wc_machine_goods) {
            $inserData = [
                'm_id' => $m_id,
                'machine_id' => $machine['machine_id'],
                'channel_code' => 'Z10',
                'g_id' => $wc_machine_goods['g_id'],
                'out_no' => $wc_machine_goods['out_no'],
                'g_name' => $wc_machine_goods['g_name'],
                'gc_id' => $wc_machine_goods['type'], //  这里传的type应该不是外层type  所以type_name未知
                'gc_name' => $wc_machine_goods['type_name'],
                'pic' => $wc_machine_goods['pic'],
                'sku' => $wc_machine_goods['sku'],
                'bar_code' => $wc_machine_goods['bar_code'],
                'retail_price' => $wc_machine_goods['retail_price'],
                'gift_points' => $wc_machine_goods['gift_points'] ?? 0,
                'sort' => array_search($wc_machine_goods['out_no'], $out_nos) + 1,
            ];
            $flag[] = $this->addWcMachineChannel($inserData);
        }
        if ($this->checkFlag($flag)) return $this->rA('虚拟货道微程商品上架完成');
        return $this->rA('上架失败');
    }

    //设置虚拟货道商品排序
    public function setWcMachineChannelListsV2($m_id, $out_nos)
    {
        $m_ids = is_array($m_id) ? $m_id : explode(',', (string)$m_id);
        $m_ids = array_values(array_unique($m_ids));
        if (empty($m_ids)) return $this->r(100, '请选择设备');

        $out_nos = is_array($out_nos) ? $out_nos : explode(',', (string)$out_nos);
        $out_nos = array_map(function ($out_no) {
            return trim((string)$out_no);
        }, $out_nos);
        $out_nos = array_values(array_unique(array_filter($out_nos, function ($out_no) {
            return $out_no !== '';
        })));
        $is_batch_off_shelf = empty($out_nos);

        $machine_maps = [];
        foreach ($m_ids as $id) {
            $machine = $this->getMachineFind(['m_id' => $id]);
            if (!$machine) continue;
            $machine_maps[$id] = $machine->toArray();
        }
        if (count($m_ids) !== count($machine_maps)) return $this->r(100, '选中的设备存在异常的设备');

        if ($is_batch_off_shelf) {
            $machine_ids_arr = array_column($machine_maps, 'machine_id');
            $operator = $this->manager ?? [];
            $log_data = [
                'm_ids'           => implode(',', $m_ids),
                'machine_ids'     => implode(',', $machine_ids_arr),
                'out_nos'         => '',
                'total_machines'  => count($m_ids),
                'total_goods'     => 0,
                'combo_count'     => 0,
                'combo_out_nos'   => json_encode([], JSON_UNESCAPED_UNICODE),
                'operator_id'     => $operator['manager_id'] ?? 0,
                'operator_name'   => $operator['nickname'] ?? '',
                'create_time'     => time(),
                'update_time'     => time(),
            ];

            $this->startTrans();
            try {
                $this->delWcMachineChannelInfo([['m_id', 'in', $m_ids]]);
                $log_id = $this->addWcMcSortLog($log_data);
                actionLog(['log_id' => $log_id, 'log_data' => $log_data], '批量下架日志主表写入', 'wc_sort_log');

                $this->commitTrans();
                return $this->rA('虚拟货道微程商品批量下架完成');
            } catch (\Throwable $e) {
                $this->rollbackTrans();
                actionLog(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], '批量下架日志写入异常', 'wc_sort_log');
                return $this->r(100, $e->getMessage());
            }
        }

        $wc_goods_type = $this->getWcGoodsTypesList([['id', '>', '0']])->toArray();
        $wc_goods_type_arr = array_column($wc_goods_type, 'name', 'id');

        $wc_goods_lists = $this->getWcGoodsList([['no', 'in', $out_nos]])->toArray();
        if (empty($wc_goods_lists)) return $this->r(100, '上架失败，找不到微程商品信息');

        $goods_map = [];
        foreach ($wc_goods_lists as $wc_goods) {
            $no = $wc_goods['no'] ?? '';
            if ($no === '') continue;
            if (!isset($goods_map[$no])) {
                $goods_map[$no] = $wc_goods;
            }
        }

        $missing_out_nos = [];
        foreach ($out_nos as $out_no) {
            if (!isset($goods_map[$out_no])) {
                $missing_out_nos[] = $out_no;
            }
        }
        if (!empty($missing_out_nos)) {
            return $this->r(100, '上架失败，以下微程商品不存在商品库信息：' . implode(',', $missing_out_nos));
        }

        $sort_map = array_flip($out_nos);
        $insert_all = [];
        $log_details = [];
        $combo_out_nos = [];
        foreach ($m_ids as $id) {
            $machine = $machine_maps[$id];
            foreach ($out_nos as $out_no) {
                if (!isset($goods_map[$out_no])) continue;
                $wc_goods = $goods_map[$out_no];
                $resourcesArray = $wc_goods['resourcesArray'] ? json_decode($wc_goods['resourcesArray'], true) : [];
                $pic = '';
                if (isset($resourcesArray[0]['url'])) $pic = ($wc_goods['resourceDomain'] ?? '') . $resourcesArray[0]['url'];

                $is_combo = (!empty($wc_goods['type']) && $wc_goods['type'] == 11)
                    || !empty($wc_goods['combination_goods']) ? 1 : 0;
                if ($is_combo && !in_array($out_no, $combo_out_nos)) {
                    $combo_out_nos[] = $out_no;
                }

                $row = [
                    'm_id' => $id,
                    'machine_id' => $machine['machine_id'],
                    'channel_code' => 'Z10',
                    'g_id' => $wc_goods['g_id'] ?? 0,
                    'out_no' => $wc_goods['no'] ?? '',
                    'g_name' => $wc_goods['name'] ?? '',
                    'gc_id' => $wc_goods['type'] ?? 0,
                    'gc_name' => $wc_goods_type_arr[$wc_goods['type']] ?? '',
                    'pic' => $pic,
                    'sku' => $wc_goods['sku'] ?? '',
                    'bar_code' => $wc_goods['sku'] ?? '',
                    'retail_price' => $wc_goods['price'] ?? 0,
                    'gift_points' => $wc_goods['gift_points'] ?? 0,
                    'sort' => isset($sort_map[$out_no]) ? $sort_map[$out_no] + 1 : 0,
                ];
                $insert_all[] = $row;

                $log_details[] = [
                    'm_id'         => $row['m_id'],
                    'machine_id'   => $row['machine_id'],
                    'out_no'       => $row['out_no'],
                    'g_id'         => $row['g_id'],
                    'g_name'       => $row['g_name'],
                    'gc_id'        => $row['gc_id'],
                    'gc_name'      => $row['gc_name'],
                    'pic'          => $row['pic'],
                    'sku'          => $row['sku'],
                    'bar_code'     => $row['bar_code'],
                    'retail_price' => $row['retail_price'],
                    'gift_points'  => $row['gift_points'],
                    'sort'         => $row['sort'],
                    'is_combo'     => $is_combo,
                ];
            }
        }
        if (empty($insert_all)) return $this->r(100, '上架失败，找不到微程商品信息');

        $machine_ids_arr = array_column($machine_maps, 'machine_id');
        $operator = $this->manager ?? [];
        $log_data = [
            'm_ids'           => implode(',', $m_ids),
            'machine_ids'     => implode(',', $machine_ids_arr),
            'out_nos'         => implode(',', $out_nos),
            'total_machines'  => count($m_ids),
            'total_goods'     => count($out_nos),
            'combo_count'     => count($combo_out_nos),
            'combo_out_nos'   => json_encode($combo_out_nos, JSON_UNESCAPED_UNICODE),
            'operator_id'     => $operator['manager_id'] ?? 0,
            'operator_name'   => $operator['nickname'] ?? '',
            'create_time'     => time(),
            'update_time'     => time(),
        ];

        $this->startTrans();
        try {
            // 先清理目标设备历史数据，再批量入库新排序
            $this->delWcMachineChannelInfo([['m_id', 'in', $m_ids]]);
            $result = $this->addWcMachineChannelMore($insert_all);
            if (!$result) {
                $this->rollbackTrans();
                return $this->rA('上架失败');
            }

            // 写入日志主表
            $log_id = $this->addWcMcSortLog($log_data);
            actionLog(['log_id' => $log_id, 'log_data' => $log_data], '排序日志主表写入', 'wc_sort_log');

            // 写入日志明细，关联 log_id
            foreach ($log_details as &$detail) {
                $detail['log_id'] = $log_id;
            }
            unset($detail);
            $detailResult = $this->addWcMcSortLogDetailMore($log_details);
            actionLog(['log_id' => $log_id, 'detail_count' => count($log_details), 'result_type' => gettype($detailResult)], '排序日志明细写入', 'wc_sort_log');

            $this->commitTrans();
            return $this->rA('虚拟货道微程商品上架完成', ['log_id' => $log_id]);
        } catch (\Throwable $e) {
            $this->rollbackTrans();
            actionLog(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], '排序日志写入异常', 'wc_sort_log');
            return $this->r(100, $e->getMessage());
        }
    }


    public function getWcMachineChannelLists($where, $pageNum = 0)
    {
        $list  = $this->getWcMachineChannelList($where, $pageNum, '*', 'sort asc')->toArray();
        $list = !$pageNum ? $list : $list['data'];
        foreach ($list as &$v) {
            $v['goods_list'] = $this->getWcGoodsLocalList(['out_no' => $v['out_no']])->toArray();

            if (!empty($v['goods_list'])) {
                $need_pic = empty($v['pic']);
                $need_price = (float)($v['retail_price'] ?? 0) == 0;
                $physical_total = 0;
                $days_price = 0;
                $today = date('Y-m-d');
                foreach ($v['goods_list'] as $item) {
                    $is_virtual = ($item['g_id'] ?? 0) == 9999;

                    // 图片处理：外层 pic 为空时，取首个非实物商品(g_id=9999)图片
                    if ($need_pic && $is_virtual && !empty($item['pic'])) {
                        $v['pic'] = $item['pic'];
                        $need_pic = false;
                    }

                    // 价格处理：外层 retail_price 为 0 时计算（实物累加 + 当日 daysInfo）
                    if ($need_price) {
                        if (!$is_virtual) {
                            $physical_total = bcadd($physical_total, $item['retail_price'] ?? 0, 2);
                            continue;
                        }

                        if (empty($item['daysInfo'])) {
                            $days_price = $item['retail_price'] ?? 0;
                            continue;
                        } else {
                            $daysInfo = json_decode($item['daysInfo'], true);
                            $matched_today_price = false;
                            if (is_array($daysInfo)) {
                                foreach ($daysInfo as $day) {
                                    if (isset($day['date']) && $day['date'] == $today) {
                                        $days_price = $day['price'] ?? 0;
                                        $matched_today_price = true;
                                        break;
                                    }
                                }
                            }
                            if (!$matched_today_price) {
                                $days_price = $item['retail_price'] ?? 0;
                            }
                        }
                    }
                }

                if ($need_price) {
                    $v['retail_price'] = bcadd($physical_total, $days_price, 2);
                    $v['retail_price'] = round($v['retail_price'], 2);
                }
            }
        }
        return  $this->rQ($list);
    }

    /**
     * 获取虚拟货道排序日志列表
     */
    public function getMcSortLogList($where, $pageNum = 0)
    {
        $eachFn = function ($item) {
            $item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';
            return $item;
        };
        return $this->rQ($this->getWcMcSortLogList($where, $pageNum, '*', 'id desc', $eachFn));
    }

    /**
     * 获取虚拟货道排序日志详情
     * 返回4个数据结构：设备列表、单品数据、组合商品数据、排序好的数据列表
     */
    public function getMcSortLogDetail($log_id)
    {
        $log = $this->getWcMcSortLogFind(['id' => $log_id]);
        if (!$log) return $this->r(100, '日志记录不存在');
        $log = $log->toArray();

        // 所有明细（已按sort排序）
        $details = $this->getWcMcSortLogDetailList(['log_id' => $log_id], 0, '*', 'sort asc')->toArray();

        // 1. 设备列表（去重），用于设备下拉框选中
        $machine_list = [];
        $seen_m_ids = [];
        foreach ($details as $row) {
            $mid = $row['m_id'];
            if (in_array($mid, $seen_m_ids)) continue;
            $seen_m_ids[] = $mid;
            $machine = $this->getMachineFind(['m_id' => $mid]);
            $machine_list[] = [
                'm_id'       => $mid,
                'machine_id' => $row['machine_id'],
                'machine_name'=> $machine ? $machine['machine_name'] : '',
            ];
        }
        if (empty($machine_list)) {
            $log_m_ids = array_map('trim', explode(',', (string)($log['m_ids'] ?? '')));
            $log_machine_ids = array_filter(array_map('trim', explode(',', (string)($log['machine_ids'] ?? ''))), function ($machine_id) {
                return $machine_id !== '';
            });
            foreach ($log_machine_ids as $index => $machine_id) {
                $machine = $this->getMachineFind(['machine_id' => $machine_id]);
                $machine_list[] = [
                    'm_id'         => $machine ? $machine['m_id'] : ($log_m_ids[$index] ?? ''),
                    'machine_id'   => $machine_id,
                    'machine_name' => $machine ? $machine['machine_name'] : '',
                ];
            }
        }

        // 2 & 3. 按 is_combo 拆分：单品 + 组合商品
        $out_nos_arr = $log['out_nos'] ? explode(',', $log['out_nos']) : [];
        $wc_goods_all = [];
        if (!empty($out_nos_arr)) {
            $wc_goods_all = $this->getWcGoodsList([['no', 'in', $out_nos_arr]])->toArray();
        }
        // 构建 out_no => goods 映射
        $goods_map = [];
        foreach ($wc_goods_all as $g) {
            $no = $g['no'] ?? '';
            if ($no !== '' && !isset($goods_map[$no])) {
                $goods_map[$no] = $g;
            }
        }

        $single_goods = [];  // 单品
        $combo_goods = [];   // 组合商品
        $sorted_list = [];   // 按out_no去重排序列表
        $seen_out_nos = [];
        foreach ($details as $row) {
            $out_no = $row['out_no'];
            if (in_array($out_no, $seen_out_nos)) continue;
            $seen_out_nos[] = $out_no;

            $goods_info = $goods_map[$out_no] ?? [];
            $resourcesArray = isset($goods_info['resourcesArray']) ? (is_string($goods_info['resourcesArray']) ? json_decode($goods_info['resourcesArray'], true) : $goods_info['resourcesArray']) : [];
            $pic = '';
            if (!empty($resourcesArray[0]['url'])) {
                $pic = ($goods_info['resourceDomain'] ?? '') . $resourcesArray[0]['url'];
            }

            $item = [
                'out_no'       => $out_no,
                'g_name'       => $row['g_name'],
                'gc_name'      => $row['gc_name'],
                'pic'          => $pic ?: $row['pic'],
                'retail_price' => $row['retail_price'],
                'sku'          => $row['sku'],
            ];

            if ($row['is_combo'] == 1) {
                $combo_goods[] = $item;
            } else {
                $single_goods[] = $item;
            }
            $sorted_list[] = $row;
        }

        return $this->rQ([
            'log'          => $log,
            'machine_list' => $machine_list,
            'single_goods' => $single_goods,
            'combo_goods'  => $combo_goods,
            'sorted_list'  => $sorted_list,
        ]);
    }

    public function syncUserRights($token)
    {
        $result = $this->syncWcUserInfo($token);
        if ($result['status'] == 200) {
            $res = json2arr($result['response']);
            if (!$res || !isset($res['data'])) {
                return ['status' => false, 'msg' => $result['response']];
            }
            return ['status' => true, 'data' => $res['data']];
        }
        return ['status' => false, 'msg' => $result['response']];;
    }

    public function synchronizeOrder($order)
    {
        // $this->syncOrder($order);
    }

    public function synchronizeOrderRefund($order)
    {
        // $this->syncOrderRefund($order);
    }
}
