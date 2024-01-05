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

    public function getAdvertisementPushList($where,$pageNum = 0, $field = "*", $order = "adv_id desc")
    {
        return AdvertisementPushModel::getList($where,$pageNum,$field,$order);
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
        return AdvertisementPushModel::destroy($where);
    }

    /**
     * 门店查询广告
     * @return mixed
     */
    public function queryAdvPush()
    {
        $where['status'] = ['<',3];
        $where['start_date'] = ['<=', time()];
        $where['end_date'] = ['>',time()];
        $where['store_id'] = $this->store['store_id'];
        $field = "adv_id,adv_title,res_id,res_title,concat('" . $this->getUrl() . "',file_path) file_path,duration_time,total_times,start_date,end_date,start_time,end_time,position,screen,screen_full,status";
        $adv = $this->getAdvertisementPushList($where,0,$field,'start_date asc');
        return $this->rQ($adv);
    }

    public function reportAdvPlay()
    {
        if (isset($this->message['data']['adv_id'])) {
            $adv = $this->getAdvertisementPushFind(['adv_id' => $this->message['data']['adv_id']]);
            if (!$adv) return $this->rFail('查无广告信息');

        }
    }
}