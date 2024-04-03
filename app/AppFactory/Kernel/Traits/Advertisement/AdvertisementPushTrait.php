<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 14:50
 */

namespace app\AppFactory\Kernel\Traits\Advertisement;


use app\AppFactory\Kernel\Model\Advertisement\AdvertisementPushModel;

trait AdvertisementPushTrait
{
    public function getAdvertisementPushFind($where,$field = "*", $order = "")
    {
        return AdvertisementPushModel::getFind($where,$field,$order);
    }

    public function getAdvertisementPushGroupList($where,$pageNum = 0,$field = "*",$group = "",$order = "")
    {
        return AdvertisementPushModel::getList($where,$pageNum,$field,$order,'',$group);
    }

    /**
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAdvertisementPushList($where,$pageNum = 0, $field = "*", $order = "adv_id desc")
    {
        return AdvertisementPushModel::getList($where,$pageNum,$field,$order,function($item){
            $update = [];
            if (isset($item['status']) && $item['status'] == 1) {
                $update['status'] = 2;
                $item['status'] = 2;
            }
            if (isset($item['end_date'])) {
                if ($item['end_date'] < strtotime(date("Y-m-d"))) {
                    $update['status'] = 3;
                    $item['status'] = 3;
                }
                if ($item['end_date'] == strtotime(date("Y-m-d")) && isset($item['end_time']) && $item['end_time'] < HourMinuteSec2int(date("H:i:s"))) {
                    $update['status'] = 3;
                    $item['status'] = 3;
                }
            }
            if ($update && isset($item['adv_id'])) {
                $update['adv_id'] = $item['adv_id'];
                AdvertisementPushModel::update($update);
            }
            return $item;
        });
    }

    public function addAdvertisementPush($insert)
    {
        $insert['creator'] = isset($this->manager['manager_id']) ? $this->manager['manager_id'] : 0;
        $adv = AdvertisementPushModel::create($insert);
        return $adv->adv_id;
    }

    public function updateAdvertisementPush($update, $where = [], $field = [])
    {
        return AdvertisementPushModel::update($update,$where,$field);
    }

    public function delAdvertisementPush($where)
    {
        return AdvertisementPushModel::whereDel($where);
    }

}