<?php

namespace app\AppFactory\Kernel\Traits\Auth;

use app\AppFactory\Kernel\Model\Auth\AuthRoleTemplateModel;
use app\AppFactory\Kernel\Model\Auth\AuthRoleTemplateNavigationModel;
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

    public function getAuthRoleTemplateNavigationList($where, $pageNum = 0, $field = "*", $order = "artnavi_id asc")
    {
        $where['is_del'] = 2;
        return AuthRoleTemplateNavigationModel::getList($where, $pageNum, $field, $order);
    }

    public function replaceAuthRoleTemplateNodes($artId, array $nodeList)
    {
        $managerId = intval($this->manager['manager_id']);
        Db::name('auth_role_template_node')->where(['art_id' => $artId, 'is_del' => 2])->update([
            'is_del' => 1,
            'update_id' => $managerId,
            'update_time' => time(),
        ]);
        foreach ($nodeList as $nodeId => $permission) {
            $nodeId = intval($nodeId);
            if ($nodeId <= 0) continue;
            $exists = Db::name('auth_role_template_node')
                ->where(['art_id' => $artId, 'node_id' => $nodeId])
                ->order('artn_id desc')
                ->find();
            $data = [
                'is_del' => 2,
                'update_id' => $managerId,
                'update_time' => time(),
            ];
            if (is_array($permission)) {
                $data['data_scope'] = $permission['data_scope'] ?? '';
            } else {
                $data['d_type'] = intval($permission);
            }
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

    public function replaceAuthRoleTemplateNavigations($artId, array $navigationList)
    {
        $managerId = intval($this->manager['manager_id']);
        Db::name('auth_role_template_navigation')->where(['art_id' => $artId, 'is_del' => 2])->update([
            'is_del' => 1,
            'update_id' => $managerId,
            'update_time' => time(),
        ]);
        foreach ($navigationList as $setting) {
            $nodeId = intval($setting['node_id'] ?? 0);
            if ($nodeId <= 0) continue;
            $exists = Db::name('auth_role_template_navigation')
                ->where(['art_id' => $artId, 'node_id' => $nodeId])
                ->order('artnavi_id desc')
                ->find();
            $data = [
                'data_scope' => $setting['data_scope'],
                'create_enabled' => intval($setting['create_enabled']),
                'delete_enabled' => intval($setting['delete_enabled']),
                'update_enabled' => intval($setting['update_enabled']),
                'query_enabled' => intval($setting['query_enabled']),
                'is_del' => 2,
                'update_id' => $managerId,
                'update_time' => time(),
            ];
            if ($exists) {
                Db::name('auth_role_template_navigation')->where(['artnavi_id' => $exists['artnavi_id']])->update($data);
                continue;
            }
            $data['art_id'] = $artId;
            $data['node_id'] = $nodeId;
            $data['creator'] = $managerId;
            $data['create_time'] = time();
            Db::name('auth_role_template_navigation')->insert($data);
        }
        return true;
    }

    public function assertRoleTemplateAssociation($roleId, $templateId, $roleAoId = 0)
    {
        $templateId = intval($templateId);
        if ($templateId <= 0) return true;
        if (!$roleAoId && $roleId) {
            $roleAoId = intval(Db::name('auth_role')->where('role_id', intval($roleId))->value('ao_id'));
        }
        $template = Db::name('auth_role_template')->where([
            'art_id' => $templateId,
            'status' => 1,
            'is_del' => 2,
        ])->find();
        if (!$template) throw new \Exception("角色权限模板不存在或未启用");
        if (intval($roleAoId) > 1 && intval($template['ao_id']) !== intval($roleAoId)) {
            throw new \Exception("角色与权限模板所属组织不一致");
        }
        return true;
    }

}
