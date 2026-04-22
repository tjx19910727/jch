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


    public function synchronizeGoodsTypesAll()
    {
        $wc_goods_type = $this->getWcGoodsTypesList([['id', '>', '0']]);
        if (!$wc_goods_type) return true;
        $wc_goods_type = $wc_goods_type->toArray();
        foreach ($wc_goods_type as $type) {
            $res = $this->app->weicheng->synchronizeGoodsTypes($type['id'], 1);
        }
        return $res;
    }

    public function synchronizeGoodsTypes($goods_type, $nowPage = 1)
    {
        $result = $this->goodsTypesSync($goods_type, $nowPage);
        if ($result['status'] != 200) {
            return $this->rA('分类商品同步失败: ' . $result['response']);
        }
        $updateData = json2arr($result['response']);

        $totalPage = isset($updateData['data']['totalPage']) ? intval($updateData['data']['totalPage']) : 1;
        $goods_lists = $updateData['data']['list'] ?? [];

        $res = $this->synchronizeGoodsLists2Db($goods_lists, $goods_type);

        // 如果还有下一页，递归处理并合并结果
        if ($nowPage < $totalPage) {
            $nextRes = $this->synchronizeGoodsTypes($goods_type, $nowPage + 1);

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

        // 仅在顶层调用时返回标准化的 rA 响应，递归内部返回原始合并结果
        if ($nowPage === 1) {
            return $this->rA('分类商品同步成功', $combined);
        }

        return $combined;
    }

    public function synchronizeGoodsAll()
    {
        $wc_goods = $this->getWcGoodsList([['id', '>', '0']])->toArray();
        foreach ($wc_goods as $v) {
            $res = $this->synchronizeGoods($v['no'], $v['type']);
            if (!$res['status']) continue;
        }
        $this->wcGoodsWriteLocal();
        return returnState('200', '分类商品同步成功');
    }

    public function synchronizeGoods($goods_no, $type)
    {
        $result = $this->goodsSync($goods_no, $type);

        if ($result['status'] == 200) {
            $res = json2arr($result['response']);
            if (!$res || !isset($res['product'])) {
                // actionLog('同步失败', $goods_no);
                return ['status' => false, 'msg' => $result['response']];
            }

            $updateData = $res['product'];
            $updateData['get_data'] = $result['response'];
            if (isset($updateData['goods']))
                $updateData['goods'] = json_encode($updateData['goods']);
            if (isset($updateData['combination_goods']))
                $updateData['combination_goods'] = json_encode($updateData['combination_goods'], JSON_UNESCAPED_UNICODE);
            if (isset($updateData['resourcesArray']))
                $updateData['resourcesArray'] = json_encode($updateData['resourcesArray'], JSON_UNESCAPED_UNICODE);
            if (isset($updateData['daysInfo']))
                $updateData['daysInfo'] = json_encode($updateData['daysInfo']);
            if (isset($updateData['present_integral']))
                $updateData['gift_points'] = $updateData['present_integral'] ?? 0;

            //type值是从goods_type带过来的，这里不要修改商品的type，否则查询不到数据
            if (isset($updateData['type'])) unset($updateData['type']);
            $res = $this->synchronizeGoods2Db($updateData);
            return ['status' => $res];
        }
        return ['status' => false, 'msg' => $result['response']];;
    }

    public function wcGoodsWriteLocal()
    {
        $wc_goods = $this->getWcGoodsList([['id', '>', '0']])->toArray();
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
        $out_nos = array_values(array_unique($out_nos));
        if (empty($out_nos)) return $this->r(100, '请选择微程商品');

        $machine_maps = [];
        foreach ($m_ids as $id) {
            $machine = $this->getMachineFind(['m_id' => $id]);
            if (!$machine) continue;
            $machine_maps[$id] = $machine->toArray();
        }
        if (count($m_ids) !== count($machine_maps)) return $this->r(100, '选中的设备存在异常的设备');

        $wc_goods_local_lists = $this->getWcGoodsLocalList([['out_no', 'in', $out_nos]])->toArray();
        if (empty($wc_goods_local_lists)) return $this->r(100, '上架失败，找不到微程商品信息');

        $goods_local_map = [];
        foreach ($wc_goods_local_lists as $wc_goods_local) {
            $outNo = $wc_goods_local['out_no'] ?? '';
            if ($outNo === '') continue;
            if (!isset($goods_local_map[$outNo])) {
                $goods_local_map[$outNo] = $wc_goods_local;
            }
        }

        $missing_out_nos = [];
        foreach ($out_nos as $out_no) {
            if (!isset($goods_local_map[$out_no])) {
                $missing_out_nos[] = $out_no;
            }
        }
        if (!empty($missing_out_nos)) {
            return $this->r(100, '上架失败，以下微程商品不存在本地化信息：' . implode(',', $missing_out_nos));
        }

        $sort_map = array_flip($out_nos);
        $insert_all = [];
        foreach ($m_ids as $id) {
            $machine = $machine_maps[$id];
            foreach ($out_nos as $out_no) {
                if (!isset($goods_local_map[$out_no])) continue;
                $wc_goods_local = $goods_local_map[$out_no];
                $insert_all[] = [
                    'm_id' => $id,
                    'machine_id' => $machine['machine_id'],
                    'channel_code' => 'Z10',
                    'g_id' => $wc_goods_local['g_id'] ?? 0,
                    'out_no' => $wc_goods_local['out_no'] ?? '',
                    'g_name' => $wc_goods_local['g_name'] ?? '',
                    'gc_id' => $wc_goods_local['gc_id'] ?? ($wc_goods_local['type'] ?? 0),
                    'gc_name' => $wc_goods_local['gc_name'] ?? ($wc_goods_local['type_name'] ?? ''),
                    'pic' => $wc_goods_local['pic'] ?? '',
                    'sku' => $wc_goods_local['sku'] ?? '',
                    'bar_code' => $wc_goods_local['bar_code'] ?? '',
                    'retail_price' => $wc_goods_local['retail_price'] ?? 0,
                    'gift_points' => $wc_goods_local['gift_points'] ?? 0,
                    'sort' => isset($sort_map[$out_no]) ? $sort_map[$out_no] + 1 : 0,
                ];
            }
        }
        if (empty($insert_all)) return $this->r(100, '上架失败，找不到微程商品信息');

        $this->startTrans();
        try {
            // 先清理目标设备历史数据，再批量入库新排序
            $this->delWcMachineChannelInfo([['m_id', 'in', $m_ids]]);
            $result = $this->addWcMachineChannelMore($insert_all);
            if (!$result) {
                $this->rollbackTrans();
                return $this->rA('上架失败');
            }
            $this->commitTrans();
            return $this->rA('虚拟货道微程商品上架完成');
        } catch (\Throwable $e) {
            $this->rollbackTrans();
            return $this->r(100, $e->getMessage());
        }
    }


    public function getWcMachineChannelLists($where, $pageNum = 0)
    {
        $list  = $this->getWcMachineChannelList($where, $pageNum, '*', 'sort asc')->toArray();
        $list = !$pageNum ? $list : $list['data'];
        foreach ($list as &$v) {
            $v['goods_list'] = $this->getWcGoodsLocalList(['out_no' => $v['out_no']])->toArray();
        }
        return  $this->rQ($list);
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
