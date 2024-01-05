<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/9
 * Time: 13:55
 */

namespace app\AppFactory\Kernel\Traits\Ali;


use app\AppFactory\Kernel\Model\Ali\AliBalanceModel;

trait AliBalanceTrait
{
    public function getAliBalanceList($where,$pageNum = 0,$field = "*", $order = "")
    {
        return AliBalanceModel::getList($where,$pageNum,$field,$order);
    }

    public function addAliBalance($insert)
    {
        $ab = AliBalanceModel::create($insert);
        return $ab->bd_id;
    }
}