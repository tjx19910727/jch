<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/9
 * Time: 10:21
 */

namespace app\AppFactory\Kernel\Traits\Wx;

use app\AppFactory\Kernel\Model\Wx\WxBalanceModel;

trait WxBalanceTrait
{
    public function getWxBalanceList($where,$pageNum = 0,$field = "*", $order = "id desc")
    {
        return WxBalanceModel::getList($where,$pageNum,$field,$order);
    }

    public function addWxBalance($insert)
    {
        if (isset($this->manager['manager_id']))  $insert['creator'] = $this->manager['manager_id'];
        $wb = WxBalanceModel::create($insert);
        return $wb->id;
    }

}