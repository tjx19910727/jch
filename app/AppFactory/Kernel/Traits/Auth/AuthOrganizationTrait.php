<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/6
 * Time: 14:45
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthOrganizationModel;

trait AuthOrganizationTrait
{
    /**
     * 获取组织字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getAuthOrganizationValue($where,$value)
    {
        return AuthOrganizationModel::getFieldValue($where,$value);
    }

    /**
     * 获取一条组织信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return AuthOrganizationModel|array|mixed|null|\think\Model
     */
    public function getAuthOrganizationFind($where,$field = "*",$order = "")
    {
        if (isset($this->manager) && $this->manager && !isset($where['creator'])) $where[] = ['creator','=',$this->manager['manager_id']];
        return AuthOrganizationModel::getFind($where,$field,$order);
    }

    /**
     * 获取组织列
     * @param $where
     * @param $column
     * @return array
     */
    public function getAuthOrganizationColumn($where,$column)
    {
        return AuthOrganizationModel::getColumn($where,$column);
    }

    /**
     * 查询组织架构列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return AuthOrganizationModel|AuthOrganizationModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getAuthOrganizationList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $result = AuthOrganizationModel::getList($where,$pageNum,$field,$order,function($item){
            $arList = $this->getAuthOrganizationRoleList(['or.ao_id' => $item['ao_id']],0,'ar.name');
            $item['roleName'] = '';
            if ($arList) {
                $arList = $arList->toArray();
                $item['roleName'] = implode(",",array_column($arList,'name'));
            }
            $item['userNum'] = $this->getAuthManagerCount(['ao_id' => $item['ao_id']]);
            return $item;
        });
        return $result;
    }

    /**
     * 增加组织
     * @param $insert
     * @return mixed
     */
    public function addAuthOrganization($insert)
    {
        if ($this->manager['ao_id']) {
            $ao = $this->getAuthOrganizationFind(['ao_id' => $this->manager['ao_id']]);
            $insert['creator'] = $this->manager['manager_id'] ?? 0;
            $insert['level'] = ($ao['level'] ?? 0) + 1;
        }
        $data = AuthOrganizationModel::create($insert);
        return $data->ao_id;
    }

    /**
     * 修改组织
     * @param $update
     * @param array $where
     * @param array $field
     * @return AuthOrganizationModel
     */
    public function updateAuthOrganization($update,$where = [],$field = [])
    {
        if (!isset($update['update_id'])) $update['update_id'] = $this->manager['manager_id'] ?? 0;
        return AuthOrganizationModel::update($update,$where,$field);
    }

    /**
     * 删除组织
     * @param $where
     * @return bool
     */
    public function delAuthOrganization($where)
    {
        return AuthOrganizationModel::whereDel($where);
    }

    /**
     * 获取某组织节点所有上级ID
     * @param $id
     * @param int $addSelf
     * @return array
     */
    public function getParentIds($id,$addSelf = 1)
    {
        if ($id) {
            if ($addSelf) $ids[] = $id;
            AuthOrganizationModel::getPAoIds(['ao_id' => $id], $ids);
            return $ids;
        }
        return [];
    }

    /**
     * 获取某组织节点所有下级ID
     * @param $id
     * @param int $addSelf
     * @return array
     */
    public function getChildIds($id,$addSelf = 1)
    {
        if ($id) {
            AuthOrganizationModel::getCAoIds(['pid' => $id], $ids);
            if ($addSelf) $ids[] = $id;
            return $ids;
        }
        return [];
    }

    /**
     * 获取某组织节点所有上下级ID
     * @param $id
     * @param int $addSelf
     * @return array
     */
    public function getPathIds($id,$addSelf = 1)
    {
        $pIds = $this->getParentIds($id,$addSelf);
        $cIds = $this->getChildIds($id,$addSelf);
        $ids = array_unique(array_merge($pIds,$cIds));
        return $ids;
    }
}