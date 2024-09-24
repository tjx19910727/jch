<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/10
 * Time: 10:23
 */

namespace app\management\controller\trip;


use app\management\controller\Common;
use app\management\validate\Trip\VTripMultipleHotel;

class TripMultipleHotel extends Common
{

    protected $field = "*";
    protected $validatePath = VTripMultipleHotel::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->tripMultipleHotel->getList($where,$pageNum,$this->field,'tmh_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->tripMultipleHotel->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $check = $this->app->tripMultipleHotel->getTripMultipleHotelFind(['tm_id' => $postData['tm_id'],'hotelId' => $postData['hotelId']]);
        if ($check) return returnState(100,lang("VTripMultiple.hotel_id_unique"));
        return $this->app->tripMultipleHotel->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->tripMultipleHotel->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->tripMultipleHotel->del($postData);
    }
}