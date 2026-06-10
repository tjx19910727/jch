<?php

namespace app\AppFactory\Management\Auth;

use app\AppFactory\Kernel\Traits\Auth\AuthRoleTemplateTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class AuthRoleTemplateClient extends ManagementClient
{
    use AuthRoleTemplateTrait;

    public function update($postData, $where = [], $field = [], $rU = 1)
    {
        try {
            $this->assertTemplateManaged(intval($postData['art_id'] ?? 0));
            $result = $this->updateAuthRoleTemplate($postData, $where, $field);
            return $rU ? $this->rU($result) : $result;
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function saveNodes($data)
    {
        try {
            $this->assertTemplateManaged(intval($data['art_id']));
        } catch (\Exception $e) {
            return $this->rTryCatch($e->getMessage());
        }
        $nodeList = json2arr($data['nodeList'] ?? []);
        if (!is_array($nodeList)) return $this->rFail("模板节点格式不正确");
        $nodeIds = array_filter(array_map('intval', array_keys($nodeList)));
        if ($nodeIds) {
            $validNodeIds = Db::name('auth_node')->where('node_id', 'in', $nodeIds)->column('node_id');
            if (count(array_unique($nodeIds)) !== count(array_unique(array_map('intval', $validNodeIds)))) {
                return $this->rFail("模板包含不存在的权限节点");
            }
        }
        foreach ($nodeList as $dType) {
            if (!in_array(intval($dType), [0, 1, 2, 3, 4, 5], true)) {
                return $this->rFail("数据权限类型不合法");
            }
        }
        $this->startTrans();
        try {
            $this->replaceAuthRoleTemplateNodes(intval($data['art_id']), $nodeList);
            return $this->checkTrans(true);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getNodes($where, $pageNum = 0)
    {
        try {
            $this->assertTemplateManaged(intval($where['art_id'] ?? 0));
        } catch (\Exception $e) {
            return $this->rTryCatch($e->getMessage());
        }
        return $this->rQ($this->getAuthRoleTemplateNodeList(
            $where,
            $pageNum,
            'artn_id,art_id,node_id,d_type',
            'artn_id asc'
        ));
    }

    public function apply($data)
    {
        try {
            $template = $this->assertTemplateManaged(intval($data['art_id']));
            $role = $this->assertRoleManaged(intval($data['role_id']));
            if (intval($role['ao_id']) > 1 && intval($template['ao_id']) !== intval($role['ao_id'])) {
                throw new \Exception("角色与权限模板所属组织不一致");
            }
        } catch (\Exception $e) {
            return $this->rTryCatch($e->getMessage());
        }
        $this->startTrans();
        try {
            $roleId = intval($data['role_id']);
            $templateId = intval($data['art_id']);
            Db::name('auth_role')->where(['role_id' => $roleId])->update([
                'template_id' => $templateId,
                'update_id' => $this->manager['manager_id'],
                'update_time' => time(),
            ]);
            return $this->checkTrans(true);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function remove($artId)
    {
        try {
            $this->assertTemplateManaged(intval($artId));
        } catch (\Exception $e) {
            return $this->rTryCatch($e->getMessage());
        }
        $used = Db::name('auth_role')->where(['template_id' => intval($artId)])->count();
        if ($used > 0) return $this->rFail("模板已被角色使用，不能删除");
        return $this->rU($this->updateAuthRoleTemplate(
            ['is_del' => 1],
            ['art_id' => intval($artId)],
            ['is_del']
        ));
    }

    protected function assertTemplateManaged($artId)
    {
        $template = Db::name('auth_role_template')->where(['art_id' => $artId, 'is_del' => 2])->find();
        if (!$template) throw new \Exception("角色权限模板不存在");
        if (intval($this->manager['ao_id']) > 1 && intval($template['ao_id']) !== intval($this->manager['ao_id'])) {
            throw new \Exception("无权操作其他组织的角色权限模板");
        }
        return $template;
    }

    protected function assertRoleManaged($roleId)
    {
        $role = Db::name('auth_role')->where(['role_id' => $roleId])->find();
        if (!$role) throw new \Exception("权限角色不存在");
        if (intval($this->manager['ao_id']) > 1 && intval($role['ao_id']) !== intval($this->manager['ao_id'])) {
            throw new \Exception("无权操作其他组织的权限角色");
        }
        return $role;
    }
}
