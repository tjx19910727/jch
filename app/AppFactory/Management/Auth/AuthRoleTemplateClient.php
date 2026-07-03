<?php

namespace app\AppFactory\Management\Auth;

use app\AppFactory\Kernel\Traits\Auth\AuthRoleTemplateTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class AuthRoleTemplateClient extends ManagementClient
{
    use AuthRoleTemplateTrait;

    private const PERMISSION_ACTIONS = ['menu', 'create', 'delete', 'update', 'query', 'export'];
    private const ACTION_FIELDS = ['create', 'delete', 'update', 'query'];
    private const DATA_SCOPES = ['organization', 'all'];

    public function getFind($where = [], $field = "*", $order = "", $rQ = 1)
    {
        $data = $this->getAuthRoleTemplateFind($where, $this->templateDetailField(), $order);
        return $rQ ? $this->rQ($data) : $data;
    }

    public function getList($where = [], $pageNum = 0, $field = "*", $order = "", $rQ = 1)
    {
        $data = $this->getAuthRoleTemplateList($where, $pageNum, $this->templateListField(), $order ?: 'art_id desc');
        return $rQ ? $this->rQ($data) : $data;
    }

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
            'artn_id,art_id,(SELECT name FROM auth_role_template art WHERE art.art_id = a.art_id) template_name,node_id,(SELECT name FROM auth_node an WHERE an.node_id = a.node_id) node_name,data_scope',
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
                'artnavi_id,art_id,(SELECT name FROM auth_role_template art WHERE art.art_id = a.art_id) template_name,node_id,(SELECT name FROM auth_node an WHERE an.node_id = a.node_id) node_name,data_scope,create_enabled,delete_enabled,update_enabled,query_enabled'
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
            $this->replaceAuthRoleTemplateNavigations(intval($data['art_id']), $expanded['navigation_list']);
            $this->commitTrans();
            return $this->r(200, '保存成功', $expanded);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 直接替换模板绑定账号。单个账号只保存一个 role_template_id，切换模板即覆盖旧值。
     */
    public function applyManagers($data)
    {
        try {
            $template = $this->assertTemplateManaged(intval($data['art_id']));
            $managerIds = $this->normalizeManagerIds($data['manager_ids'] ?? []);
            $templateId = intval($template['art_id']);
            $templateAoId = intval($template['ao_id']);
            $moveIds = [];
            if ($managerIds) {
                $query = Db::name('auth_manager')
                    ->where('manager_id', 'in', $managerIds)
                    ->where('status', 1);
                if ($templateAoId > 1) $query->where('ao_id', $templateAoId);
                $validManagerIds = $query->column('manager_id');
                if (count(array_unique(array_map('intval', $validManagerIds))) !== count($managerIds)) {
                    throw new \Exception("账号不存在、已停用或与模板所属组织不一致");
                }
                $moveIds = Db::name('auth_manager')
                    ->where('manager_id', 'in', $managerIds)
                    ->where('role_template_id', '>', 0)
                    ->where('role_template_id', '<>', $templateId)
                    ->where('use_role_template', 1)
                    ->column('manager_id');
                $moveIds = array_values(array_unique(array_map('intval', $moveIds)));
            }
        } catch (\Exception $e) {
            return $this->rTryCatch($e->getMessage());
        }

        $this->startTrans();
        try {
            $activeQuery = Db::name('auth_manager')
                ->where(['role_template_id' => $templateId, 'use_role_template' => 1]);
            if ($templateAoId > 1) $activeQuery->where('ao_id', $templateAoId);
            $activeIds = array_map('intval', $activeQuery->column('manager_id'));
            $removeIds = array_diff($activeIds, $managerIds);
            $addIds = array_diff($managerIds, $activeIds);

            if ($removeIds) {
                Db::name('auth_manager')
                    ->where('manager_id', 'in', $removeIds)
                    ->update([
                        'role_template_id' => 0,
                        'use_role_template' => 2,
                        'update_id' => $this->manager['manager_id'],
                        'update_time' => time(),
                    ]);
            }
            if ($addIds) {
                Db::name('auth_manager')
                    ->where('manager_id', 'in', $addIds)
                    ->update([
                        'role_template_id' => $templateId,
                        'use_role_template' => 1,
                        'update_id' => $this->manager['manager_id'],
                        'update_time' => time(),
                    ]);
            }
            $this->commitTrans();
            return $this->r(200, '保存成功', [
                'art_id' => $templateId,
                'manager_ids' => $managerIds,
                'added_manager_ids' => array_values($addIds),
                'removed_manager_ids' => array_values($removeIds),
                'moved_manager_ids' => $moveIds,
            ]);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getManagers($data)
    {
        try {
            $template = $this->assertTemplateManaged(intval($data['art_id'] ?? 0));
        } catch (\Exception $e) {
            return $this->rTryCatch($e->getMessage());
        }
        $query = Db::name('auth_manager')
            ->alias('au')
            ->join('auth_organization ao', 'ao.ao_id = au.ao_id', 'left')
            ->where([
                'au.role_template_id' => intval($template['art_id']),
                'au.use_role_template' => 1,
            ])
            ->field('au.manager_id,au.nickname,au.account,au.status,au.ao_id,ao.organization_name,au.role_template_id')
            ->order('au.manager_id asc');
        if (intval($template['ao_id']) > 1) $query->where('au.ao_id', intval($template['ao_id']));
        return $this->rQ($query->select());
    }

    public function remove($artId)
    {
        try {
            $this->assertTemplateManaged(intval($artId));
        } catch (\Exception $e) {
            return $this->rTryCatch($e->getMessage());
        }
        $used = Db::name('auth_manager')->where(['role_template_id' => intval($artId), 'use_role_template' => 1])->count();
        if ($used > 0) return $this->rFail("模板已被账号使用，不能删除");
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

    protected function normalizeManagerIds($managerIds)
    {
        if (is_string($managerIds)) {
            $decoded = json_decode($managerIds, true);
            $managerIds = is_array($decoded) ? $decoded : explode(',', $managerIds);
        }
        if (!is_array($managerIds)) return [];
        return array_values(array_unique(array_filter(array_map('intval', $managerIds))));
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
        $navigationList = [];
        foreach ($topNavigationList as $setting) {
            if (!is_array($setting)) throw new \Exception("顶级导航权限格式不正确");
            $topNodeId = intval($setting['node_id'] ?? 0);
            if (!isset($nodeMap[$topNodeId]) || intval($nodeMap[$topNodeId]['pid']) !== 0) {
                throw new \Exception("只能配置启用的顶级导航节点");
            }
            if (isset($selected[$topNodeId])) throw new \Exception("顶级导航不能重复配置");
            $topSetting = $this->normalizeNavigationSetting($setting, null, $topNodeId, $nodeMap);
            $navigationList[$topNodeId] = $this->navigationPersistenceRow($topSetting);
            $this->applyNavigationPermission($topNodeId, $topSetting, $nodeList, $nodeMap, $childrenMap);
            $topSetting['children'] = $this->expandChildNavigationSettings(
                $this->arrayInput($setting['children'] ?? []),
                $topNodeId,
                $topSetting,
                $navigationList,
                $nodeList,
                $nodeMap,
                $childrenMap
            );
            $selected[$topNodeId] = $this->navigationResponseRow($topSetting);
        }

        ksort($nodeList);
        return [
            'top_navigation_list' => array_values($selected),
            'navigation_list' => array_values($navigationList),
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

    protected function expandChildNavigationSettings(array $settings, $topNodeId, array $parentSetting, array &$navigationList, array &$nodeList, array $nodeMap, array $childrenMap)
    {
        $children = [];
        foreach ($settings as $setting) {
            if (!is_array($setting)) throw new \Exception("子导航权限格式不正确");
            $nodeId = intval($setting['node_id'] ?? 0);
            if (!isset($nodeMap[$nodeId]) || !$this->isDescendantNode($nodeId, $topNodeId, $nodeMap)) {
                throw new \Exception("子导航必须属于对应顶级导航");
            }
            if (isset($navigationList[$nodeId])) throw new \Exception("导航节点不能重复配置");
            $childSetting = $this->normalizeNavigationSetting($setting, $parentSetting, $nodeId, $nodeMap);
            $navigationList[$nodeId] = $this->navigationPersistenceRow($childSetting);
            $this->applyNavigationPermission($nodeId, $childSetting, $nodeList, $nodeMap, $childrenMap);
            $childSetting['children'] = $this->expandChildNavigationSettings(
                $this->arrayInput($setting['children'] ?? []),
                $topNodeId,
                $childSetting,
                $navigationList,
                $nodeList,
                $nodeMap,
                $childrenMap
            );
            $children[] = $this->navigationResponseRow($childSetting);
        }
        return $children;
    }

    protected function arrayInput($value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        return is_array($value) ? $value : [];
    }

    protected function normalizeNavigationSetting(array $setting, $parentSetting, $nodeId, array $nodeMap)
    {
        $dataScope = array_key_exists('data_scope', $setting)
            ? (strval($setting['data_scope']) === 'all' ? 'all' : 'organization')
            : ($parentSetting['data_scope'] ?? 'organization');
        $normalized = [
            'node_id' => intval($nodeId),
            'node_name' => $nodeMap[intval($nodeId)]['name'] ?? '',
            'data_scope' => $dataScope,
        ];
        $enabledActions = [];
        foreach (self::ACTION_FIELDS as $action) {
            $field = $action . '_enabled';
            $enabled = array_key_exists($field, $setting)
                ? intval($setting[$field]) === 1
                : intval($parentSetting[$field] ?? 0) === 1;
            $normalized[$field] = $enabled ? 1 : 0;
            if ($enabled) $enabledActions[] = $action;
        }
        $normalized['_enabled_actions'] = $enabledActions;
        return $normalized;
    }

    protected function navigationPersistenceRow(array $setting)
    {
        return [
            'node_id' => $setting['node_id'],
            'node_name' => $setting['node_name'],
            'data_scope' => $setting['data_scope'],
            'create_enabled' => $setting['create_enabled'],
            'delete_enabled' => $setting['delete_enabled'],
            'update_enabled' => $setting['update_enabled'],
            'query_enabled' => $setting['query_enabled'],
        ];
    }

    protected function navigationResponseRow(array $setting)
    {
        $row = $this->navigationPersistenceRow($setting);
        $row['children'] = $setting['children'] ?? [];
        return $row;
    }

    protected function applyNavigationPermission($nodeId, array $setting, array &$nodeList, array $nodeMap, array $childrenMap)
    {
        $nodeIds = array_merge([intval($nodeId)], $this->collectPermissionDescendantIds($nodeId, $childrenMap));
        foreach ($nodeIds as $id) {
            unset($nodeList[$id]);
        }
        $enabledActions = $setting['_enabled_actions'] ?? [];
        foreach ($nodeIds as $id) {
            $action = $nodeMap[$id]['permission_action'];
            if ($action === 'menu' || in_array($action, $enabledActions, true) || ($action === 'export' && in_array('query', $enabledActions, true))) {
                $nodeList[$id] = ['data_scope' => $setting['data_scope']];
            }
        }
    }

    protected function isDescendantNode($nodeId, $ancestorNodeId, array $nodeMap)
    {
        $pid = intval($nodeMap[intval($nodeId)]['pid'] ?? 0);
        while ($pid > 0) {
            if ($pid === intval($ancestorNodeId)) return true;
            $pid = intval($nodeMap[$pid]['pid'] ?? 0);
        }
        return false;
    }

    protected function templateListField()
    {
        return "art_id,name,`desc`,ao_id,status,creator,update_id,create_time,update_time,"
            . "(SELECT COUNT(*) FROM auth_manager au WHERE au.role_template_id = a.art_id AND au.use_role_template = 1) manager_num,"
            . "(SELECT nickname FROM auth_manager au WHERE au.manager_id = a.update_id) update_nickname";
    }

    protected function templateDetailField()
    {
        return "art_id,name,`desc`,ao_id,"
            . "(SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = auth_role_template.ao_id) organization_name,"
            . "status,creator,"
            . "(SELECT nickname FROM auth_manager au WHERE au.manager_id = auth_role_template.creator) creator_nickname,"
            . "update_id,"
            . "(SELECT nickname FROM auth_manager au WHERE au.manager_id = auth_role_template.update_id) update_nickname,"
            . "(SELECT COUNT(*) FROM auth_manager au WHERE au.role_template_id = auth_role_template.art_id AND au.use_role_template = 1) manager_num,"
            . "create_time,update_time";
    }

    protected function buildPermissionNodeTree(array $nodes, $pid = 0, array $settings = [])
    {
        $tree = [];
        foreach ($nodes as $node) {
            if (intval($node['pid']) !== intval($pid)) continue;
            $node['permission_action'] = in_array($node['permission_action'] ?? '', self::PERMISSION_ACTIONS, true)
                ? $node['permission_action']
                : 'unclassified';
            $node['template_setting'] = $settings[intval($node['node_id'])] ?? null;
            $node['children'] = $this->buildPermissionNodeTree($nodes, intval($node['node_id']), $settings);
            $tree[] = $node;
        }
        return $tree;
    }
}
