<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:08
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthRoleNodeModel extends BaseModel
{
    protected $name = "auth_role_node";
    protected $pk = "rn_id";

    protected $schema = [
        "rn_id" => "int",
        "role_id" => "int",
        "node_id" => "int",
        "d_type" => "int",
        "is_del" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];

    public function authNode()
    {
        return $this->hasOne(AuthNodeModel::class,'node_id','node_id');
    }


    /**
     * 获取关联权限节点列表
     * @param $where
     * @param $pageNum
     * @param string $field
     * @param string $order
     * @return AuthRoleNodeModel|AuthRoleNodeModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getJoinNodeList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $data = self::alias("rn")
            ->join("auth_node an","rn.node_id = an.node_id",'left')
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