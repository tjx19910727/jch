<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/6
 * Time: 11:27
 */

namespace app\AppFactory\Kernel\Model\Activity\Fd;


use app\AppFactory\Kernel\Model\BaseModel;
use think\Model;

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

    /**
     * 查询列表前处理
     * @param Model $model
     */
    public static function onAfterRead(Model $model)
    {
        // 未开始的修改为进行中
        $where[] = ['start_date','>',strtotime(date("Y-m-d"))];
        $where[] = ['end_date','>=',strtotime(date("Y-m-d"))];
        $where['status'] = 1;
        $update['status'] = 2;
        self::update($update,$where);
        // 进行中的修改为已结束
        $where2[] = ['end_date','<',strtotime(date("Y-m-d"))];
        $where2['status'] = 2;
        $update['status'] = 3;
        self::update($update,$where2);
    }
}