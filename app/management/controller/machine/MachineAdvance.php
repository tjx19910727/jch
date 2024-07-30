<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/29
 * Time: 10:05
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineAdvance;

class MachineAdvance extends Common
{

    protected $validatePath = VMachineAdvance::class . ".";

    /**
     * 获取广告推送列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['adv_title' => "like"]);
        if (!isset($where['push_type'])) $where[] = ['push_type','between',[2,3]];
        return returnData($this->app->advertisementPush->getAdvertisementPushList($where,($postData['pageNum'] ?? 0),'*',"create_time desc"));
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
        $pageNum = input('pageNum',0);
        $pushType = input("pushType");
        $where = $this->getWhere(["push_type" => $pushType,"adv_title" => $adv_title,"machine_id|machine_name" => $machine],false,['adv_title' => "like","machine_id|machine_name" => "like"]);
        $where[] = ['status',"<",3];
        if (!isset($where['push_type'])) $where[] = ['push_type','between',[2,3]];
        // 机器分组
        if ($groupType == 1) {
            if ($machine) $where[] = ['machine_id|machine_name','like',"%".$machine."%"];
            $group = "m_id";
            $field = "m_id,machine_id,machine_name,count(adv_id) adv_num";
        }
        if ($groupType == 2) {
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
        $result = $this->app->advertisementPush->delAdvertisementPush($where);
        return $result ? returnState(200,'删除成功') : returnState(100,'删除失败');
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


    public function getRecord()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["adv_title" => "like","machine_id" => "like","res_title" => "like"]);
        if (!isset($where['push_type']))  $where[] = ['push_type','between',[2,3]];
        return $this->app->advertisementRecord->getAdvertisementRecordList($where,$pageNum,"*",'play_time desc');
    }
}