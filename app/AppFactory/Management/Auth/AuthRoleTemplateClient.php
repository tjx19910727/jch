<?php

namespace app\AppFactory\Management\Auth;

use app\AppFactory\Kernel\Traits\Auth\AuthRoleTemplateTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class AuthRoleTemplateClient extends ManagementClient
{
    use AuthRoleTemplateTrait;

    private const PERMISSION_ACTIONS = ['menu', 'create', 'delete', 'update', 'query'];
    private const DATA_SCOPES = ['organization', 'all'];

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
            $this->replaceAuthRoleTemplateNavigations(intval($data['art_id']), []);
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
            'artn_id,art_id,node_id,data_scope',
            'artn_id asc'
        ));
    }

    public function getTopNavigationNodes(array $excludedNodeIds = [], $artId = 0)
    {
        $settings = [];
        if ($artId > 0) {
            try {
                $this->assertTemplateManaged($artId);
            } catch (\Exception $e) {
                return $this->rTryCatch($e->getMessage());
            }
            $settings = $this->getAuthRoleTemplateNavigationList(
                ['art_id' => $artId],
                0,
                'node_id,data_scope,create_enabled,delete_enabled,update_enabled,query_enabled'
            )->toArray();
            $settings = array_column($settings, null, 'node_id');
        }
        $query = Db::name('auth_node')
            ->where(['status' => 1])
            ->field('node_id,pid,name,url,sort,type,is_button,permission_action');
        if ($excludedNodeIds) $query->where('node_id', 'not in', $excludedNodeIds);
        $nodes = $query
            ->order('sort asc,node_id asc')
            ->select()
            ->toArray();
        return $this->r(200, '查询成功', $this->buildPermissionNodeTree($nodes, 0, $settings));
    }

    public function saveTopNavigationNodes($data, array $excludedNodeIds = [])
    {
        try {
            $this->assertTemplateManaged(intval($data['art_id']));
            if (!array_key_exists('top_navigation_list', $data)) {
                return $this->rFail("顶级导航权限列表不能为空");
            }
            $topNavigationList = json2arr($data['top_navigation_list'] ?? []);
            if (!is_array($topNavigationList)) return $this->rFail("顶级导航权限格式不正确");

            $query = Db::name('auth_node')
                ->where(['status' => 1])
                ->field('node_id,pid,name,url,permission_action');
            if ($excludedNodeIds) $query->where('node_id', 'not in', $excludedNodeIds);
            $nodes = $query
                ->order('sort asc,node_id asc')
                ->select()
                ->toArray();
            $expanded = $this->expandTopNavigationPermissions($topNavigationList, $nodes);
        } catch (\Exception $e) {
            return $this->rTryCatch($e->getMessage());
        }

        $this->startTrans();
        try {
            $this->replaceAuthRoleTemplateNodes(intval($data['art_id']), $expanded['node_list']);
            $this->replaceAuthRoleTemplateNavigations(intval($data['art_id']), $expanded['top_navigation_list']);
            $this->commitTrans();
            return $this->r(200, '保存成功', $expanded);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
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

    protected function expandTopNavigationPermissions(array $topNavigationList, array $nodes)
    {
        $nodeMap = [];
        $childrenMap = [];
        foreach ($nodes as $node) {
            $nodeId = intval($node['node_id']);
            $node['permission_action'] = in_array($node['permission_action'] ?? '', self::PERMISSION_ACTIONS, true)
                ? $node['permission_action']
                : 'unclassified';
            $nodeMap[$nodeId] = $node;
            $childrenMap[intval($node['pid'])][] = $nodeId;
        }
        foreach ($childrenMap as $parentId => $childIds) {
            if ($parentId > 0 && isset($nodeMap[$parentId])) {
                $nodeMap[$parentId]['permission_action'] = 'menu';
            }
        }

        $nodeList = [];
        $selected = [];
        foreach ($topNavigationList as $setting) {
            if (!is_array($setting)) throw new \Exception("顶级导航权限格式不正确");
            $topNodeId = intval($setting['node_id'] ?? 0);
            if (!isset($nodeMap[$topNodeId]) || intval($nodeMap[$topNodeId]['pid']) !== 0) {
                throw new \Exception("只能配置启用的顶级导航节点");
            }
            if (isset($selected[$topNodeId])) throw new \Exception("顶级导航不能重复配置");
            $dataScope = strval($setting['data_scope'] ?? '');
            if (!in_array($dataScope, self::DATA_SCOPES, true)) {
                throw new \Exception("每个顶级导航必须选择查账号所属组织或查全部");
            }
            $enabledActions = [];
            foreach (['create', 'delete', 'update', 'query'] as $action) {
                if (intval($setting[$action . '_enabled'] ?? 0) === 1) $enabledActions[] = $action;
            }
            if (!$enabledActions) throw new \Exception("每个顶级导航至少选择一种接口权限");

            $selected[$topNodeId] = [
                'node_id' => $topNodeId,
                'data_scope' => $dataScope,
                'create_enabled' => in_array('create', $enabledActions, true) ? 1 : 0,
                'delete_enabled' => in_array('delete', $enabledActions, true) ? 1 : 0,
                'update_enabled' => in_array('update', $enabledActions, true) ? 1 : 0,
                'query_enabled' => in_array('query', $enabledActions, true) ? 1 : 0,
            ];
            $descendantIds = $this->collectPermissionDescendantIds($topNodeId, $childrenMap);
            $nodeList[$topNodeId] = ['data_scope' => $dataScope];
            foreach (array_merge([$topNodeId], $descendantIds) as $nodeId) {
                $action = $nodeMap[$nodeId]['permission_action'];
                if ($action === 'menu' || in_array($action, $enabledActions, true)) {
                    $nodeList[$nodeId] = ['data_scope' => $dataScope];
                }
            }
        }

        ksort($nodeList);
        return [
            'top_navigation_list' => array_values($selected),
            'node_list' => $nodeList,
            'node_count' => count($nodeList),
        ];
    }

    protected function collectPermissionDescendantIds($nodeId, array $childrenMap)
    {
        $result = [];
        foreach ($childrenMap[intval($nodeId)] ?? [] as $childId) {
            $result[] = $childId;
            $result = array_merge($result, $this->collectPermissionDescendantIds($childId, $childrenMap));
        }
        return $result;
    }

    protected function buildPermissionNodeTree(array $nodes, $pid = 0, array $settings = [])
    {
        $tree = [];
        foreach ($nodes as $node) {
            if (intval($node['pid']) !== intval($pid)) continue;
            $node['permission_action'] = in_array($node['permission_action'] ?? '', self::PERMISSION_ACTIONS, true)
                ? $node['permission_action']
                : 'unclassified';
            if (intval($pid) === 0) {
                $node['template_setting'] = $settings[intval($node['node_id'])] ?? null;
            }
            $node['children'] = $this->buildPermissionNodeTree($nodes, intval($node['node_id']), $settings);
            $tree[] = $node;
        }
        return $tree;
    }
}
