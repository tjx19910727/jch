<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 11:05
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\ActivityFullDecModel;

trait ActivityFullDecTrait
{
    /**
     * 获取满减活动列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return ActivityFullDecModel|ActivityFullDecModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getActivityFullDecList($where,$pageNum = 0,$field = "*",$order = "afd_id desc")
    {
        return ActivityFullDecModel::getList($where,$pageNum,$field,$order);
    }

    /**
     * 获取一条满减活动信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return ActivityFullDecModel|array|mixed|null|\think\Model
     */
    public function getActivityFullDecFind($where,$field = '*',$order = "afd_id desc")
    {
        return ActivityFullDecModel::getFind($where,$field,$order);
    }

    /**
     * 添加满减活动信息
     * @param $insert
     * @return mixed
     */
    public function addActivityFullDec($insert)
    {
        if (isset($this->manager['manager_id'])) $insert['creator'] = $this->manager['manager_id'];
        $afd = ActivityFullDecModel::create($insert);
        return $afd->afd_id;
    }

    /**
     * 修改满减活动主体信息
     * @param $update
     * @param array $where
     * @param array $field
     * @return ActivityFullDecModel
     */
    public function updateActivityFullDec($update,$where = [],$field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'];
        return ActivityFullDecModel::update($update,$where,$field);
    }

    /**
     * 删除满减活动信息
     * @param $where
     * @return bool
     */
    public function delActivityFullDesc($where)
    {
        return ActivityFullDecModel::destroy($where);
    }
}