<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/14
 * Time: 17:14
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthManagerMachineModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "auth_manager_machine";

    public static function joinAuthManager($where,$field = "*",$order = "")
    {
        $data = self::alias("amm")
            ->join("auth_manager au","au.manager_id = amm.manager_id",'left')
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }

    public static function joinMachine($where,$field = "*", $order = "")
    {
        $data = self::alias("amm")
            ->join("machine m","amm.m_id = m.m_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }
}