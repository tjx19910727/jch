<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/27
 * Time: 8:45
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineFreeHotel;

class MachineFreeHotel extends Common
{

    protected $field = "*";
    protected $validatePath = VMachineFreeHotel::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        if (!isset($where['mf_id']) || !$where['mf_id']) return returnState(100,lang("VMachineFree.mf_id_require"));
        return $this->app->machineFreeHotel->getList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineFreeHotel->getFind($where);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $check = $this->app->machineFreeHotel->getMachineFreeHotelFind(['mf_id' => $postData['mf_id'],'hotelId' => $postData['hotelId']]);
        if ($check) return returnState(100,lang("VMachineFree.hotelId_unique"));
        return $this->app->machineFreeHotel->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineFreeHotel->update($postData,[],['hotelTel','imageUrl','address','openYear','renovationYear','roomQuantity','guestOverallRating','rise_fall_ratio']);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineFreeHotel->del($postData);
    }
}