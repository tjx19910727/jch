<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/8
 * Time: 11:19
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthOrganizationRoleModel;

trait AuthOrganizationRoleTrait
{
    /**
     * 获取组织关联角色字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getAuthOrganizationRoleValue($where,$value)
    {
        return AuthOrganizationRoleModel::getFieldValue($where,$value);
    }

    /**
     * 获取一条组织关联角色字信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return AuthOrganizationRoleModel|array|mixed|null|\think\Model
     */
    public function getAuthOrganizationRoleFind($where,$field = "*",$order = "")
    {
        if (isset($this->manager) && $this->manager && !isset($where['creator'])) $where[] = ['creator','=',$this->manager['manager_id']];
        return AuthOrganizationRoleModel::getFind($where,$field,$order);
    }

    /**
     * 获取组织关联角色列
     * @param $where
     * @param $column
     * @return array
     */
    public function getAuthOrganizationRoleColumn($where,$column)
    {
        return AuthOrganizationRoleModel::getColumn($where,$column);
    }

    /**
     * 查询组织关联角色列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return AuthOrganizationRoleModel|AuthOrganizationRoleModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getAuthOrganizationRoleList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $where['is_del'] = 2;
        $result = AuthOrganizationRoleModel::getJoinRoleList($where,$pageNum,$field,$order);
        return $result;
    }

    /**
     * 增加组织关联角色信息
     * @param $insert
     * @return mixed
     */
    public function addAuthOrganizationRole($insert)
    {
        $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $insert['pid'] = $this->manager['manager_id'] ?? 0;
        $insert['level'] = ($this->manager['level'] ?? 0) + 1;
        $data = AuthOrganizationRoleModel::create($insert);
        return $data->ao_id;
    }

    /**
     * 修改组织关联角色信息
     * @param $update
     * @param array $where
     * @param array $field
     * @return AuthOrganizationRoleModel
     */
    public function updateAuthOrganizationRole($update,$where = [],$field = [])
    {
        if (!isset($update['update_id'])) $update['update_id'] = $this->manager['manager_id'] ?? 0;
        return AuthOrganizationRoleModel::update($update,$where,$field);
    }

    /**
     * 删除组织关联角色信息
     * @param $where
     * @return bool
     */
    public function delAuthOrganizationRole($where)
    {
        return AuthOrganizationRoleModel::destroy($where);
    }
}