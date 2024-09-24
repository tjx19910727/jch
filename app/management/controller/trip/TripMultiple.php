<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 17:05
 */

namespace app\management\controller\trip;


use app\management\controller\Common;
use app\management\validate\Trip\VTripMultiple;

class TripMultiple extends Common
{

    protected $field = "*";
    protected $validatePath = VTripMultiple::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->tripMultiple->getTmList($where,$pageNum,$this->field,'tm_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->tripMultiple->getTmFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->tripMultiple->addTm($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->tripMultiple->updateTm($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->tripMultiple->delTm($postData['tm_id']);
    }
}