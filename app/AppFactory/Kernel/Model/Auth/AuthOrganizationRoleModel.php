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
        $data = self::alias("or")
            ->join("auth_role ar",'ar.role_id = or.role_id',"left")
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