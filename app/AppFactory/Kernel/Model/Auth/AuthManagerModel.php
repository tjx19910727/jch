<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 15:43
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthManagerModel extends BaseModel
{
    protected $name = "auth_manager";
    protected $pk = "manager_id";
    protected $schema = [
        "manager_id" => "int",
        "ao_id" => "int",
        "nickname" => "string",
        "account" => "string",
        "password" => "string",
        "pid" => "int",
        "level" => "int",
        "user_id" => "int",
        "openid" => "string",
        "sex" => "int",
        "pic" => "string",
        "balance" => "float",
        "frozen" => "float",
        "withdrawal" => "float",
        "bill_account" => "string",
        "real_name" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];

    /**
     * 获取关联组织账号列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return AuthManagerModel|AuthManagerModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getJoinOrganizationList($where,$pageNum = 0,$field = "*", $order = "")
    {
        $data = self::alias("au")
            ->join("auth_organization ao","ao.ao_id = au.ao_id")
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