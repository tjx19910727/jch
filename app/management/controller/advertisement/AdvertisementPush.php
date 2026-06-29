<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 14:47
 */

namespace app\management\controller\advertisement;


use app\management\controller\Common;

class AdvertisementPush extends Common
{
    protected $validatePath = 'app\management\validate\VAdvertisement.';

    /**
     * 获取广告推送列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['adv_title' => "like"]);
        $where['push_type'] = 1;
        return $this->app->advertisementPush->getList($where,($postData['pageNum'] ?? 0),'*',"create_time desc");
    }

    /**
     * 获取广告分组统计列表
     * @return array|string
     */
    public function getGroupList()
    {
        $machine = input("machine");
        $adv_title = input("adv_title");
        $groupType = input('groupType',1);
        $m_id = input('m_id');
        $pageNum = input('pageNum',0);
        $is_zero = input('is_zero', 0);
        $where = $this->getWhere([]);
        if ($m_id) {
            strpos($m_id,",") === false ? $where['m_id'] = $m_id : $where[] = ['m_id','in',explode(',',$m_id)];
        }
        $where['push_type'] = 1;
        // 机器分组
        if ($groupType == 1) {
            if (!$m_id) {
                if ($machine) $where[] = ['machine_id|machine_name', 'like', "%" . $machine . "%"];
                else {
                    $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'machine_id');
                    if ($machineIds) $where[] = ['machine_id', 'in', $machineIds];
                }
            }
            $playableCondition = "(ap.end_date > UNIX_TIMESTAMP(CURDATE()) OR (ap.end_date = UNIX_TIMESTAMP(CURDATE()) AND ap.end_time >= HOUR(CURTIME())*3600 + MINUTE(CURTIME())*60 + SECOND(CURTIME())))";
            if ($is_zero == 1) {
                $where['raw'] = "(SELECT COUNT(*) FROM advertisement_push ap WHERE ap.m_id = a.m_id AND {$playableCondition}) = 0";
            } elseif ($is_zero == 2) {
                $where['raw'] = "(SELECT COUNT(*) FROM advertisement_push ap WHERE ap.m_id = a.m_id AND {$playableCondition}) > 0";
            }
            $group = "m_id";
            $field = "m_id,machine_id,(SELECT machine_name FROM machine m WHERE m.m_id = a.m_id limit 1 ) machine_name,SUM(CASE WHEN (end_date > UNIX_TIMESTAMP(CURDATE()) OR (end_date = UNIX_TIMESTAMP(CURDATE()) AND end_time >= HOUR(CURTIME())*3600 + MINUTE(CURTIME())*60 + SECOND(CURTIME()))) THEN 1 ELSE 0 END) adv_num";
        }
        if ($groupType == 2) {
            $where[] = ['status',"<",3];
            if ($adv_title) $where[] = ['adv_title',"like","%" . $adv_title . "%"];
            $group = "batch_num";
            $field = "batch_num,adv_title,file_path,type,start_date,end_date,start_time,end_time,position,screen,screen_full,count(m_id) machine_num,status";
        }
        return $this->app->advertisementPush->getGroupList($where,$pageNum,$field,$group);
    }

    /**
     * 广告推送
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        $postData = json2arr($postData);
        try { $this->validate($postData,$this->validatePath . 'addPush');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->advertisementPush->addMorePush($postData);
    }

    /**
     * 修改广告推送数据
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'updatePush');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->advertisementPush->updatePush($postData);
    }

    /**
     * 删除广告推送
     * @return mixed
     */
    public function del()
    {
        $id = input('adv_id');
        strpos($id,",") === false ? $where['adv_id'] = $id : $where[] = ['adv_id','in',$id];
        return $this->app->advertisementPush->del($where);
    }

    /**
     * 上架下架广告
     * @return array|bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function upDown()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'upDown');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        if (!isset($postData['adv_id']) && isset($postData['m_id'])) $postData['push_type'] = 1;
        return $this->app->advertisementPush->upDown($postData);
    }

    /**
     * 触发广告更新按钮
     * @return array|string
     */
    public function triggerUpdateAD()
    {
        $adv_ids = input("adv_id");
        return $this->app->advertisementPush->triggerUpdate([['adv_id','in',$adv_ids]]);
    }
}