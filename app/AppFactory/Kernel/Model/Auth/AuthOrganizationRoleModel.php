<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/8
 * Time: 11:16
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthOrganizationRoleModel extends BaseModel
{
    protected $pk = "or_id";
    protected $name = "auth_organization_role";
    protected $schema = [
        "or_id" => "int",
        "ao_id" => "int",
        "role_id" => "int",
        "is_del" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];

    protected static function JoinRole($where,$field,$order)
    {
        return self::alias("or")
            ->join("auth_role ar",'ar.role_id = or.role_id',"left")
            ->where($where)
            ->field($field)
            ->order($order);
    }

    /**
     * 获取一条关联权限角色信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return AuthOrganizationRoleModel|array|mixed|null|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getJoinRoleFind($where,$field = "*",$order = "")
    {
        $data = self::JoinRole($where,$field,$order);
        return $data->find();
    }

    /**
     * 获取关联权限角色列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return AuthOrganizationRoleModel|AuthOrganizationRoleModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getJoinRoleList($where,$pageNum = 0,$field = "*", $order = "")
    {
        $data = self::JoinRole($where,$field,$order);
        if ($pageNum) {
            $data = $data->paginate($pageNum);
        } else {
            $data = $data->select();
        }
        return $data;
    }
}