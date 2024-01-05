<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:24
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthRoleNodeModel;

trait AuthRoleNodeTrait
{

    public function getAuthRoleNodeColumn($where,$column)
    {
        return AuthRoleNodeModel::getColumn($where,$column);
    }

    public function getAuthRoleNodeFind($where,$field = "*",$order = "")
    {
        $where['is_del'] = 2;
        return AuthRoleNodeModel::getFind($where,$field,$order);
    }

    public function getAuthRoleNodeList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $where['is_del'] = 2;
        $data = AuthRoleNodeModel::getJoinNodeList($where,$pageNum,$field,$order);
        return $data;
    }

    public function addAuthRoleNode($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $data = AuthRoleNodeModel::create($insert);
        return $data->rn_id;
    }

    /**
     * 批量绑定
     * @param $insertAll
     * @return \think\Collection
     * @throws \Exception
     */
    public function addMoreAuthRoleNode($insertAll)
    {
        $authRoleNode = new AuthRoleNodeModel();
        return $authRoleNode->saveAll($insertAll);
//        return AuthRoleNodeModel::addMore($insertAll);
    }

    public function updateAuthRoleNode($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        return AuthRoleNodeModel::update($update,$where,$field);
    }

    public function delAuthRoleNode($where)
    {
        return AuthRoleNodeModel::destroy($where);
    }
}