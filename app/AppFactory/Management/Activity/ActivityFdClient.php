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
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Activity\VActivityFd;

class ActivityFdClient extends ManagementClient
{
    use ActivityFdTrait,ActivityFdContentTrait,ActivityMachineTrait;
    use GoodsTrait,MachineTrait;

    public function getFdAmFind($where,$field = "*")
    {
        $fd = $this->getActivityFdFind($where,$field);
        if ($fd) {
            $fd = $fd->toArray();
            $fd['content'] = $this->getActivityFdContentList($where)->toArray();
            $fd['machineList'] = $this->getActivityMachineList(['a_id' => $fd['fd_id'], 'a_type' => 2],0,'am_id,m_id,machine_id,machine_name');
        }
        return $this->rQ($fd);
    }

    /**
     * 添加满减满送活动
     * @param $postData
     * @return array|string
     */
    public function addFd($postData)
    {
        $content = json2arr($postData['content']);
        $mList = explode(",",$postData['machineList']);
        unset($postData['content'],$postData['machineList']);
        $this->startTrans();
        $fd_id = $this->addActivityFd($postData);
        if ($fd_id) {
            foreach ($content as $key => $value) {
                try {
                    validate(VActivityFd::class)->scene("addContent")->check($value);
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    return $this->rValidate($e->getMessage());
                }
                $value['fd_id'] = $fd_id;
                $value['fd_name'] = $postData['fd_name'];
                if (isset($value['g_id'])) {
                    $g = $this->getGoodsFind(['g_id' => $value['g_id']], 'g_name,sku,pic,gc_id,gc_name');
                    if (!$g) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("VActivityFd.goods_no_data"));
                    }
                    $g = $g->toArray();
                    $value = array_merge($value,$g);
                }
                $insertAll[] = $value;
            }
            $this->addActivityFdContentMore($insertAll);
            foreach ($mList as $mk => $mv) {
                $insertAm['a_id'] = $fd_id;
                $insertAm['a_type'] = 2;
                $m = $this->getMachineFind(['machine_id' => $mv],"m_id,machine_id,machine_name");
                if (!$m) {
                    $this->rollbackTrans();
                    return $this->r(100,$this->lang("VActivityFd.machine_no_data"));
                }
                $m = $m->toArray();
                $insertAm = array_merge($insertAm,$m);
                $insertAmAll[] = $insertAm;
            }
            $flag[] = $this->addActivityMachineMore($insertAmAll);
            $fd = $this->getActivityFdFind(['fd_id' => $fd_id]);
            if ($fd) {
                $fd['content'] = $this->getActivityFdContentList(['fd_id' => $fd_id]);
                $fd['machineList'] = $this->getActivityMachineList(['a_id' => $fd_id,'a_type' => 2],0,'am_id,m_id,machine_id,machine_name');
                $this->commitTrans();
                return $this->r(200,$this->lang("add_success"),$fd);
            }
        }
        $this->rollbackTrans();
        return $this->r(100,$this->lang("add_fail"));
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
        unset($postData['content'],$postData['delContent'],$postData['machineList']);
        $flag = [];
        $this->startTrans();
        $flag[] = $this->updateActivityFd($postData);

        if ($delContent) $this->delActivityFdContent([['fdc_id','in',$delContent]]);
        if ($content) {
            $insertAll = [];
            foreach ($content as $key => $value) {
                if (isset($value['fdc_id'])) {
                    $flag[] = $this->updateActivityFdContent($value);
                } else {
                    $value['fd_id'] = $postData['fd_id'];
                    $value['fd_name'] = ($postData['fd_name'] ? $postData['fd_name'] : $this->getActivityFdValue(['fd_id' => $postData['fd_id']],'fd_name'));
                    if (isset($value['g_id'])) {
                        $g = $this->getGoodsFind(['g_id' => $value['g_id']], 'g_name,sku,pic,gc_id,gc_name');
                        if (!$g) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VActivityFd.goods_no_data"));
                        }
                        $g = $g->toArray();
                        $value = array_merge($value,$g);
                    }
                    $insertAll[] = $value;
                }
            }
            if ($insertAll) $this->addActivityFdContentMore($insertAll);
        }
        $insert = [
            "a_id" => $postData['fd_id'],
            "a_type" => 2,
        ];
        if ($machineList) {
            $oldAmList = $this->getActivityMachineColumn(['a_id' => $postData['fd_id'],'a_type' => 2],'machine_id');
            $delAmList = array_diff($oldAmList,$machineList);
            $addAmList = array_diff($machineList,$oldAmList);
            if ($addAmList) {
                $amResult = $this->addAm($insert, $addAmList);
                if ($amResult !== true) {
                    $this->rollbackTrans();
                    return $this->rFail($amResult);
                }
                $flag[] = 1;
            }
            if ($delAmList) $flag[] = $this->delActivityMachine(['a_id' => $postData['fd_id'],'a_type' => 2, ['machine_id','in', $delAmList]]);
        }

        $check = $this->checkFlag($flag);
        return $this->checkTrans($check);
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