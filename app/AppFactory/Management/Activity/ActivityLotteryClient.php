<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/19
 * Time: 16:47
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryConfigTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Activity\VActivityLottery;

class ActivityLotteryClient extends ManagementClient
{
    use ActivityLotteryTrait,ActivityLotteryConfigTrait,ActivityLotteryContentTrait,ActivityGoodsTrait,ActivityMachineTrait;
    use GoodsTrait,MachineTrait;

    public function getAlFind($where, $field = "*", $order = "")
    {
        $al = $this->getActivityLotteryFind($where,$field,$order);
        $al['content'] = $this->getActivityLotteryContentList(['al_id' => $al['al_id']],0,'c_id,content_name,retain_num,probability,g_id,g_name,sku','c_id asc');
        $al['config'] = $this->getActivityLotteryConfigList(['al_id' => $al['al_id']],0,'alc_id,active_num,active_type,gifts_num,designated_gift,button_pic','alc_id asc');
        $al['machineList'] = $this->getActivityMachineList(['a_id' => $al['al_id'],'a_type' => 3],0,'m_id,machine_id,machine_name');
        return $this->r(200,'查询成功',$al);
    }
    /**
     * 添加付费抽奖活动
     * @param $postData
     * @return bool|string
     */
    public function addAl($postData)
    {
        $config = json2arr($postData['config']);
        $content = json2arr($postData['content']);
        $machineList = json2arr($postData['machineList']);
        unset($postData['config'],$postData['content'],$postData['machineList']);
        if ($postData['start_time'] && $postData['start_time'] <= time()) {
            $postData['status'] = 2;
        }
        $flag = [];
        $this->startTrans();
        try {
            $al_id = $this->addActivityLottery($postData);
            if ($al_id) {
                $insertConfigAll = [];
                $totalProbability = 0;
                foreach ($content as $ck => $cv) {
                    try {
                        validate(VActivityLottery::class)->scene("addContent")->check($cv);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rValidate($e->getMessage());
                    }
                    $insertContent = $cv;
                    $insertContent['al_id'] = $al_id;

                    $g = $this->getGoodsFind(['g_id' => $cv['g_id']], 'g_id,g_name,sku');
                    if (!$g) return $this->rFail($this->lang("VGoods.goods_no_data"));

                    $insertContent = array_merge($insertContent, $g->toArray());
                    $c_id = $this->addActivityLotteryContent($insertContent);
                    $content[$ck]['c_id'] = $c_id;
                    $flag[] = $c_id;

                    $totalProbability += $cv['probability'];
                }
                if ($totalProbability != 100) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VActivityLottery.probability_no_100"));
                }
                foreach ($config as $kc => $kv) {
                    try {
                        validate(VActivityLottery::class)->scene("addConfig")->check($kv);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rValidate($e->getMessage());
                    }
                    if ($kv['designated_gift'] > 0) {
                        $designated_gift = $content[$kv['designated_gift']]['c_id'];
                        $kv['designated_gift'] = "$designated_gift";
                    }
                    $insertConfig = $kv;
                    $insertConfig['al_id'] = "$al_id";
                    $insertConfigAll[] = $insertConfig;
                }
                $flag[] = $this->addActivityLotteryConfigMore($insertConfigAll);
                // 指定设备
                foreach ($machineList as $mk => $mv) {
                    try {
                        validate(VActivityLottery::class)->scene("machineList")->check($mv);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rValidate($e->getMessage());
                    }
                    $insertAm = $this->getMachineFind(['m_id' => $mv['m_id']], 'm_id,machine_id,machine_name')->toArray();
                    $insertAm['a_type'] = 3;
                    $insertAm['a_id'] = $al_id;
                    $flag[] = $this->addActivityMachine($insertAm);
                }
            }
            $check = $this->checkFlag($flag);
            return $this->checkTrans($check);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function updateAl($postData)
    {
        $this->startTrans();
        try {
            $config = isset($postData['config']) ? json2arr($postData['config']) : [];
            $content = isset($postData['content']) ? json2arr($postData['content']) : [];
            $machineList = isset($postData['machineList']) ? json2arr($postData['machineList']) : [];
            $delConfig = isset($postData['delConfig']) ? explode(",",$postData['delConfig']) : [];
            $delContent = isset($postData['delContent']) ? explode(",",$postData['delContent']) : [];
            unset($postData['config'], $postData['content'], $postData['machineList'], $postData['delConfig'], $postData['delContent']);

            if ($postData) $this->updateActivityLottery($postData);
            if ($delContent) $this->delActivityLotteryContent([['c_id', 'in', $delContent]]);
            if ($content) {
                foreach ($content as $ck => $cv) {
                    if (isset($cv['c_id'])) {
                        $this->updateActivityLotteryContent($cv);
                    } else {
                        try {
                            validate(VActivityLottery::class)->scene("addContent")->check($cv);
                        } catch (\Exception $e) {
                            $this->rollbackTrans();
                            return $this->rValidate($e->getMessage());
                        }
                        $insertContent = $cv;
                        $insertContent['al_id'] = $postData['al_id'];
                        $g = $this->getGoodsFind(['g_id' => $cv['g_id']], 'g_id,g_name,sku');
                        if (!$g) return $this->rFail($this->lang("VGoods.goods_no_data"));
                        $insertContent = array_merge($insertContent, $g->toArray());
                        $c_id = $this->addActivityLotteryContent($insertContent);
                        $content[$ck]['c_id'] = $c_id;
                        $flag[] = $c_id;
                    }

                }
                $totalProbability = $this->getActivityLotteryContentSum(['al_id' => $postData['al_id']], 'probability');
                if ($totalProbability != 100) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VActivityLottery.probability_no_100"));
                }
            }
            if ($config) {
                $insertConfigAll = [];
                foreach ($config as $conK => $conV) {
                    if (isset($conV['alc_id'])) {
                        $this->updateActivityLotteryConfig($conV);
                    } else {
                        $insertConfig = $conV;
                        $insertConfig["al_id"] = $postData['al_id'];
                        $insertConfigAll[] = $insertConfig;
                    }
                }
                if ($insertConfigAll) $this->addActivityLotteryConfigMore($insertConfigAll);
            }
            if ($delConfig) $this->delActivityLotteryConfig([['alc_id', 'in', $delConfig]]);
            if ($machineList) {
                $oldMid = $this->getActivityMachineColumn(['a_id' => $postData['al_id'], 'a_type' => 3], 'm_id');
                $machineIds = array_column($machineList, 'm_id');
                $addList = array_diff($machineIds, $oldMid);
                $delList = array_diff($oldMid, $machineIds);
                if ($addList) {
                    foreach ($addList as $addK => $addV) {
                        $insertAm = $this->getMachineFind(['m_id' => $addV], 'm_id,machine_id,machine_name')->toArray();
                        $insertAm['a_type'] = 3;
                        $insertAm['a_id'] = $postData['al_id'];
                        $this->addActivityMachine($insertAm);
                    }
                }
                if ($delList) $this->delActivityMachine([['m_id', 'in', $delList]]);
            }
            $this->commitTrans();
            return $this->r(200, '操作成功');
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    public function delAl($postData)
    {
        $al = $this->getActivityLotteryFind(['al_id' => $postData['al_id']]);
        if (!$al) {
            return $this->rFail($this->lang("al_no_data"));
        }

        $where['al_id'] = $postData['al_id'];
        $this->delActivityLottery($where);
        $this->delActivityLotteryConfig($where);
        $this->delActivityLotteryContent($where);

        $whereAgAm['a_id'] = $postData['al_id'];
        $whereAgAm['a_type'] = 3;
        $this->delActivityMachine($whereAgAm);

        return $this->r(200,$this->lang("action_success"));
    }

    public function alTakeDown($where)
    {
        return $this->rU($this->updateActivityLottery(['status' => 4],$where,['status']));
    }
}