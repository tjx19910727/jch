<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/6
 * Time: 11:27
 */

namespace app\AppFactory\Kernel\Model\Activity\Fd;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityFdModel extends BaseModel
{
    protected $pk = "fd_id";
    protected $name = "activity_fd";

    public static function getListByMachine($where,$field = "*", $order = "")
    {
        $data = self::alias("fd")
            ->join("activity_machine am","am.a_id = fd.fd_id AND am.a_type = 2","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }
}