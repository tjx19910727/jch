<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:06
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthManagerRoleModel extends BaseModel
{
    protected $name = "auth_manager_role";
    protected $pk = "mr_id";

    protected $schema = [
        "mr_id" => "int",
        "manager_id" => "int",
        "role_id" => "int",
        "is_del" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];

    public function getIsDelAttr($value)
    {
        $IsDel = [1 => "已删除",2 => "未删除"];
        return $IsDel[$value];
    }

    public static function getJoinRoleList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $data = self::alias("mr")
            ->join("auth_role ar","ar.role_id = mr.role_id","left")
            ->where($where)
            ->field($field)
            ->order($order);
        if ($pageNum) {
            $data = $data->paginate($pageNum);
        } else {
            $data = $data->select();
        }
        return $data;
    }
}