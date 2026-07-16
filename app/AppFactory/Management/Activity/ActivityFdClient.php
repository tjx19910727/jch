<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/26
 * Time: 10:11
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityFdContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcGoodsTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Activity\VActivityFd;

class ActivityFdClient extends ManagementClient
{
    use ActivityFdTrait,ActivityFdContentTrait,ActivityMachineTrait;
    use GoodsTrait,MachineTrait,WcGoodsTrait;

    /** 查询满减活动列表，并补充普通规则和微程线上商品规则。 */
    public function getList($where = [], $pageNum = 0, $field = "*", $order = "", $rQ = 1)
    {
        $data = $this->getActivityFdList($where, $pageNum, $field, $order, function ($fd) {
            return $this->appendFdGoodsLists($fd);
        });
        return $rQ ? $this->rQ($data) : $data;
    }

    public function getFdAmFind($where,$field = "*")
    {
        $fd = $this->getActivityFdFind($where,$field);
        if ($fd) {
            $fd = $this->appendFdGoodsLists($fd);
            $fd['machineList'] = $this->getActivityMachineList(['a_id' => $fd['fd_id'], 'a_type' => 2],0,'am_id,m_id,machine_id,machine_name');
        }
        return $this->rQ($fd);
    }

    /** 将满减规则拆分为普通规则和微程线上商品列表，便于后台直接回显。 */
    protected function appendFdGoodsLists($fd)
    {
        $fd = is_object($fd) && method_exists($fd, 'toArray') ? $fd->toArray() : (array)$fd;
        $allContent = $this->getActivityFdContentList(['fd_id' => $fd['fd_id']], 0, '*', 'fdc_sort asc,fdc_id asc');
        $allContent = is_object($allContent) && method_exists($allContent, 'toArray') ? $allContent->toArray() : (array)$allContent;
        $fd['content'] = [];
        $fd['onlineGoodsList'] = [];
        foreach ($allContent as $item) {
            if (intval(isset($item['goods_source']) ? $item['goods_source'] : 1) === 2) {
                $fd['onlineGoodsList'][] = $item;
            } else {
                $fd['content'][] = $item;
            }
        }
        return $fd;
    }

    /**
     * 添加满减满送活动
     * @param $postData
     * @return array|string
     */
    public function addFd($postData)
    {
        $content = json2arr($postData['content']);
        $onlineGoodsList = $this->normalizeOnlineGoodsList($postData['onlineGoodsList'] ?? []);
        $mList = explode(",",$postData['machineList']);
        // 新增接口兼容从已有活动复制的请求，历史主键和修改专用字段不得写入新记录。
        unset($postData['fd_id'],$postData['delContent'],$postData['content'],$postData['onlineGoodsList'],$postData['machineList']);

        if ($postData['start_date'] && $postData['start_date'] <= strtotime(date("Y-m-d"))) {
            $postData['status'] = 2;
        }
        if (!isset($postData['ao_id'])) $postData['ao_id'] = $this->manager['ao_id'];
        $this->startTrans();
        try {
            $fd_id = $this->addActivityFd($postData);
            if ($fd_id) {
                foreach ($content as $key => $value) {
                    unset($value['fdc_id'], $value['fd_id']);
                    try {
                        validate(VActivityFd::class)->scene("addContent")->check($value);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rValidate($e->getMessage());
                    }
                    $value['fd_id'] = $fd_id;
                    $value['fd_name'] = $postData['fd_name'];
                    if (intval($value['goods_source'] ?? 1) === 2) {
                        $sourceNo = trim(strval($value['source_no'] ?? $value['condition_value'] ?? ''));
                        $goods = $this->getWcGoodsFind(['no' => $sourceNo]);
                        if (!$goods) {
                            $this->rollbackTrans();
                            return $this->rValidate('查无线上商品信息：' . $sourceNo);
                        }
                        $goods = is_object($goods) && method_exists($goods, 'toArray') ? $goods->toArray() : (array)$goods;
                        $value['goods_source'] = 2;
                        $value['source_no'] = $sourceNo;
                        $value['condition_value'] = $sourceNo;
                        $value['g_id'] = 0;
                        $value['g_name'] = $goods['name'] ?? '';
                        $value['pic'] = $goods['pic'] ?? '';
                        $value['sku'] = $sourceNo;
                    }
                    if ($postData["condition_type"] == 3 && intval($value['goods_source'] ?? 1) !== 2) {
                        $goods =  $this->getGoodsFind(['g_id' => $value['g_id']], 'g_id,g_name,sku,pic,gc_id,gc_name');
                        if(!isset($value['condition_value'])){
                            $value['condition_value'] = $goods['sku'];
                        }
                        if (!$goods) {
                            $this->rollbackTrans();
                            return $this->rValidate($this->lang("VActivityFd.g_id_require"));
                        }
                        $value['g_id'] = $goods['g_id'];
                        $value['g_name'] = $goods['g_name'];
                        $value['pic'] = $goods['pic'];
                        $value['sku'] = $goods['sku'];
                    }
                    if (isset($value['g_id']) && intval($value['goods_source'] ?? 1) !== 2) {
                        $g = $this->getGoodsFind(['g_id' => $value['g_id']], 'g_name,sku,pic,gc_id,gc_name');
                        if (!$g) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VActivityFd.goods_no_data"));
                        }
                        $g = $g->toArray();
                        $value = array_merge($value, $g);
                    }
                    $insertAll[] = $value;
                }
                $this->addActivityFdContentMore($insertAll);
                // 处理线上商品列表
                if ($onlineGoodsList) {
                    $onlineInsertAll = [];
                    foreach ($onlineGoodsList as $onlineItem) {
                        $sourceNo = is_array($onlineItem) ? trim(strval($onlineItem['source_no'] ?? $onlineItem['out_no'] ?? '')) : trim(strval($onlineItem));
                        if ($sourceNo === '') {
                            $this->rollbackTrans();
                            return $this->rValidate('线上商品编码不能为空');
                        }
                        $goods = $this->getWcGoodsFind(['no' => $sourceNo]);
                        if (!$goods) {
                            $this->rollbackTrans();
                            return $this->rValidate('查无线上商品信息：' . $sourceNo);
                        }
                        $goods = is_object($goods) && method_exists($goods, 'toArray') ? $goods->toArray() : (array)$goods;
                        $onlineInsertAll[] = [
                            'fd_id' => $fd_id,
                            'fd_name' => $postData['fd_name'],
                            'condition_value' => $sourceNo,
                            'goods_source' => 2,
                            'source_no' => $sourceNo,
                            'g_id' => 0,
                            'g_name' => $goods['name'] ?? '',
                            'pic' => $goods['pic'] ?? '',
                            'sku' => $sourceNo,
                            'active_value' => 0,
                        ];
                    }
                    if ($onlineInsertAll) {
                        $this->addActivityFdContentMore($onlineInsertAll);
                    }
                }
                foreach ($mList as $mk => $mv) {
                    $insertAm['a_id'] = $fd_id;
                    $insertAm['a_type'] = 2;
                    $m = $this->getMachineFind(['machine_id' => $mv], "m_id,machine_id,machine_name");
                    if (!$m) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("VActivityFd.machine_no_data"));
                    }
                    $m = $m->toArray();
                    $insertAm = array_merge($insertAm, $m);
                    $insertAmAll[] = $insertAm;
                }
                $flag[] = $this->addActivityMachineMore($insertAmAll);
                $fd = $this->getActivityFdFind(['fd_id' => $fd_id]);
                if ($fd) {
                    $fd['content'] = $this->getActivityFdContentList(['fd_id' => $fd_id]);
                    $fd['machineList'] = $this->getActivityMachineList(['a_id' => $fd_id, 'a_type' => 2], 0, 'am_id,m_id,machine_id,machine_name');
                    $this->commitTrans();
                    return $this->r(200, $this->lang("add_success"), $fd);
                }
            }
            $this->rollbackTrans();
            return $this->r(100, $this->lang("add_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 修改满减满送活动
     * @param $postData
     * @return bool|string
     */
    public function updateFd($postData)
    {
        $machineList = explode(",",$postData['machineList']);
        $delContent = $postData['delContent'];
        $content = json2arr($postData['content']);
        $hasOnlineGoodsList = array_key_exists('onlineGoodsList', $postData);
        $onlineGoodsList = $this->normalizeOnlineGoodsList($postData['onlineGoodsList'] ?? []);
        unset($postData['content'],$postData['delContent'],$postData['onlineGoodsList'],$postData['machineList']);
        $flag = [];
        $this->startTrans();

        try {
            $flag[] = $this->updateActivityFd($postData);
            if ($delContent) $this->delActivityFdContent([['fdc_id', 'in', $delContent]]);
            if ($content) {
                $insertAll = [];
                foreach ($content as $key => $value) {
                    if (isset($value['fdc_id'])) {
                        $flag[] = $this->updateActivityFdContent($value);
                    } else {
                        $value['fd_id'] = $postData['fd_id'];
                        $value['fd_name'] = ($postData['fd_name'] ? $postData['fd_name'] : $this->getActivityFdValue(['fd_id' => $postData['fd_id']], 'fd_name'));

                        if (intval($value['goods_source'] ?? 1) === 2) {
                            $sourceNo = trim(strval($value['source_no'] ?? $value['condition_value'] ?? ''));
                            $goods = $this->getWcGoodsFind(['no' => $sourceNo]);
                            if (!$goods) {
                                $this->rollbackTrans();
                                return $this->rValidate('查无线上商品信息：' . $sourceNo);
                            }
                            $goods = is_object($goods) && method_exists($goods, 'toArray') ? $goods->toArray() : (array)$goods;
                            $value['goods_source'] = 2;
                            $value['source_no'] = $sourceNo;
                            $value['condition_value'] = $sourceNo;
                            $value['g_id'] = 0;
                            $value['g_name'] = $goods['name'] ?? '';
                            $value['pic'] = $goods['pic'] ?? '';
                            $value['sku'] = $sourceNo;
                        }

                        if ($postData["condition_type"] == 3 && intval($value['goods_source'] ?? 1) !== 2) {
                            $goods = $this->getGoodsFind(['sku' => $value['condition_value']]);
                            if (!$goods) {
                                $this->rollbackTrans();
                                return $this->rValidate($this->lang("VActivityFd.g_id_require"));
                            }
                            $value['g_id'] = $goods['g_id'];
                            $value['g_name'] = $goods['g_name'];
                            $value['pic'] = $goods['pic'];
                            $value['sku'] = $goods['sku'];
                        }
                        if (isset($value['g_id']) && intval($value['goods_source'] ?? 1) !== 2) {
                            $g = $this->getGoodsFind(['g_id' => $value['g_id']], 'g_name,sku,pic,gc_id,gc_name');
                            if (!$g) {
                                $this->rollbackTrans();
                                return $this->r(100, $this->lang("VActivityFd.goods_no_data"));
                            }
                            $g = $g->toArray();
                            $value = array_merge($value, $g);
                        }
                        $insertAll[] = $value;
                    }
                }
                if ($insertAll) $this->addActivityFdContentMore($insertAll);
            }
            // 处理线上商品列表（先删除原有线上商品记录，再重新插入）
            if ($hasOnlineGoodsList) {
                $this->delActivityFdContent(['fd_id' => $postData['fd_id'], 'goods_source' => 2]);
                $onlineInsertAll = [];
                foreach ($onlineGoodsList as $onlineItem) {
                    $sourceNo = is_array($onlineItem) ? trim(strval($onlineItem['source_no'] ?? $onlineItem['out_no'] ?? '')) : trim(strval($onlineItem));
                    if ($sourceNo === '') {
                        $this->rollbackTrans();
                        return $this->rValidate('线上商品编码不能为空');
                    }
                    $goods = $this->getWcGoodsFind(['no' => $sourceNo]);
                    if (!$goods) {
                        $this->rollbackTrans();
                        return $this->rValidate('查无线上商品信息：' . $sourceNo);
                    }
                    $goods = is_object($goods) && method_exists($goods, 'toArray') ? $goods->toArray() : (array)$goods;
                    $fdName = $postData['fd_name'] ?: $this->getActivityFdValue(['fd_id' => $postData['fd_id']], 'fd_name');
                    $onlineInsertAll[] = [
                        'fd_id' => $postData['fd_id'],
                        'fd_name' => $fdName,
                        'condition_value' => $sourceNo,
                        'goods_source' => 2,
                        'source_no' => $sourceNo,
                        'g_id' => 0,
                        'g_name' => $goods['name'] ?? '',
                        'pic' => $goods['pic'] ?? '',
                        'sku' => $sourceNo,
                        'active_value' => 0,
                    ];
                }
                if ($onlineInsertAll) {
                    $this->addActivityFdContentMore($onlineInsertAll);
                }
            }
            $insert = [
                "a_id" => $postData['fd_id'],
                "a_type" => 2,
            ];
            if ($machineList) {
                $oldAmList = $this->getActivityMachineColumn(['a_id' => $postData['fd_id'], 'a_type' => 2], 'machine_id');
                $delAmList = array_diff($oldAmList, $machineList);
                $addAmList = array_diff($machineList, $oldAmList);
                if ($addAmList) {
                    $amResult = $this->addAm($insert, $addAmList);
                    if ($amResult !== true) {
                        $this->rollbackTrans();
                        return $this->rFail($amResult);
                    }
                    $flag[] = 1;
                }
                if ($delAmList) $flag[] = $this->delActivityMachine(['a_id' => $postData['fd_id'], 'a_type' => 2, ['machine_id', 'in', $delAmList]]);
            }
            $check = $this->checkFlag($flag);
            return $this->checkTrans($check);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    /** 兼容数组、JSON数组字符串及逗号分隔的线上商品编码。 */
    protected function normalizeOnlineGoodsList($value)
    {
        if (is_array($value)) return $value;
        $value = trim(strval($value));
        if ($value === '') return [];
        $decoded = json_decode($value, true);
        if (is_array($decoded)) return $decoded;
        return array_values(array_filter(array_map('trim', explode(',', $value)), function ($item) {
            return $item !== '';
        }));
    }

    /**
     * 删除满减满送活动
     * @param $postData
     * @return array|string
     */
    public function delFd($postData)
    {
        return $this->rD($this->delActivityFd($postData));
    }

    public function fdTakeDown($where)
    {
        return $this->rU($this->updateActivityFd(['status' => 4],$where,['status']));
    }
}
