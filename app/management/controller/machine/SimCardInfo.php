<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/21
 * Time: 11:30
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class SimCardInfo extends Common
{
    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, [
            'machine_id' => 'like',
            'iccid' => 'like',
            'msisdn' => 'like',
            'carrier' => 'like',
            'imei' => 'like',
            'device_card_status' => 'like',
        ]);
        return  $this->app->simCardInfo->getListData($where, $pageNum, $this->field, 'id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->simCardInfo->getFindData($where, $this->field);
    }
}
