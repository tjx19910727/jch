<?php

namespace app\AppFactory\Kernel\Traits\Auth;

use app\AppFactory\Kernel\Model\Auth\AuthRoleTemplateModel;
use app\AppFactory\Kernel\Model\Auth\AuthRoleTemplateNodeModel;
use think\facade\Db;

trait AuthRoleTemplateTrait
{
    public function getAuthRoleTemplateFind($where, $field = "*", $order = "")
    {
        $where['is_del'] = 2;
        return AuthRoleTemplateModel::getFind($where, $field, $order);
    }

    public function getAuthRoleTemplateList($where, $pageNum = 0, $field = "*", $order = "art_id desc")
    {
        $where['is_del'] = 2;
        return AuthRoleTemplateModel::getList($where, $pageNum, $field, $order);
    }

    public function addAuthRoleTemplate($insert)
    {
        $insert['ao_id'] = $insert['ao_id'] ?? $this->manager['ao_id'];
        $insert['creator'] = $this->manager['manager_id'];
        $insert['is_del'] = 2;
        $data = AuthRoleTemplateModel::create($insert);
        return $data->art_id;
    }

    public function updateAuthRoleTemplate($update, $where = [], $field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        return AuthRoleTemplateModel::update($update, $where, $field);
    }

    public function getAuthRoleTemplateNodeList($where, $pageNum = 0, $field = "*", $order = "artn_id asc")
    {
        $where['is_del'] = 2;
        return AuthRoleTemplateNodeModel::getList($where, $pageNum, $field, $order);
    }

    public function replaceAuthRoleTemplateNodes($artId, array $nodeList)
    {
        $managerId = intval($this->manager['manager_id']);
        Db::name('auth_role_template_node')->where(['art_id' => $artId, 'is_del' => 2])->update([
            'is_del' => 1,
            'update_id' => $managerId,
            'update_time' => time(),
        ]);
        foreach ($nodeList as $nodeId => $dType) {
            $nodeId = intval($nodeId);
            if ($nodeId <= 0) continue;
            $exists = Db::name('auth_role_template_node')
                ->where(['art_id' => $artId, 'node_id' => $nodeId])
                ->order('artn_id desc')
                ->find();
            $data = [
                'd_type' => intval($dType),
                'is_del' => 2,
                'update_id' => $managerId,
                'update_time' => time(),
            ];
            if ($exists) {
                Db::name('auth_role_template_node')->where(['artn_id' => $exists['artn_id']])->update($data);
                continue;
            }
            $data['art_id'] = $artId;
            $data['node_id'] = $nodeId;
            $data['creator'] = $managerId;
            $data['create_time'] = time();
            Db::name('auth_role_template_node')->insert($data);
        }
        return true;
    }

    public function applyAuthRoleTemplateToRole($roleId, $templateId = 0)
    {
        $role = Db::name('auth_role')->where(['role_id' => intval($roleId)])->find();
        if (!$role) throw new \Exception("权限角色不存在");
        $templateId = intval($templateId ?: ($role['template_id'] ?? 0));
        if ($templateId <= 0) return true;

        $template = Db::name('auth_role_template')->where([
            'art_id' => $templateId,
            'status' => 1,
            'is_del' => 2,
        ])->find();
        if (!$template) throw new \Exception("角色权限模板不存在或未启用");
        if (intval($role['ao_id']) > 1 && intval($template['ao_id']) !== intval($role['ao_id'])) {
            throw new \Exception("角色与权限模板所属组织不一致");
        }

        $nodes = Db::name('auth_role_template_node')
            ->where(['art_id' => $templateId, 'is_del' => 2])
            ->order('artn_id asc')
            ->select()
            ->toArray();
        $managerId = intval($this->manager['manager_id']);
        Db::name('auth_role_node')->where(['role_id' => $roleId, 'is_del' => 2])->update([
            'is_del' => 1,
            'update_id' => $managerId,
            'update_time' => time(),
        ]);
        foreach ($nodes as $node) {
            $exists = Db::name('auth_role_node')
                ->where(['role_id' => $roleId, 'node_id' => $node['node_id']])
                ->order('rn_id desc')
                ->find();
            $data = [
                'd_type' => intval($node['d_type']),
                'is_del' => 2,
                'update_id' => $managerId,
                'update_time' => time(),
            ];
            if ($exists) {
                Db::name('auth_role_node')->where(['rn_id' => $exists['rn_id']])->update($data);
                continue;
            }
            $data['role_id'] = $roleId;
            $data['node_id'] = intval($node['node_id']);
            $data['creator'] = $managerId;
            $data['create_time'] = time();
            Db::name('auth_role_node')->insert($data);
        }
        return true;
    }
}
