<?php

namespace app\management\controller\auth;

use app\management\controller\Common;
use think\facade\Db;

class AuthRoleTemplate extends Common
{
    protected $validatePath = 'app\management\validate\VAuth.';

    public function getFind()
    {
        $where = $this->getWhere(input(), false, ['name' => 'like']);
        return $this->app->authRoleTemplate->getFind($where);
    }

    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['name' => 'like']);
        return $this->app->authRoleTemplate->getList($where, $postData['pageNum'] ?? 0);
    }

    public function add()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'AuthRoleTemplateAdd'); }
        catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->authRoleTemplate->add($postData);
    }

    public function update()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'AuthRoleTemplateUpdate'); }
        catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->authRoleTemplate->update($postData);
    }

    public function del()
    {
        $postData = input();
        return $this->app->authRoleTemplate->remove($postData['art_id']);
    }

    public function getNodes()
    {
        $postData = input();
        return $this->app->authRoleTemplate->getNodes(
            ['art_id' => $postData['art_id']],
            $postData['pageNum'] ?? 0
        );
    }

    public function saveNodes()
    {
        $postData = json2arr(input());
        try { $this->validate($postData, $this->validatePath . 'AuthRoleTemplateNodes'); }
        catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->authRoleTemplate->saveNodes($postData);
    }

    public function getTopNavigationNodes()
    {
        $postData = input();
        return $this->app->authRoleTemplate->getTopNavigationNodes(
            $this->getExcludedTemplateNodeIds(),
            intval($postData['art_id'] ?? 0)
        );
    }

    public function saveTopNavigationNodes()
    {
        $postData = json2arr(input());
        try { $this->validate($postData, $this->validatePath . 'AuthRoleTemplateTopNavigationNodes'); }
        catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->authRoleTemplate->saveTopNavigationNodes($postData, $this->getExcludedTemplateNodeIds());
    }

    public function apply()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'AuthRoleTemplateApply'); }
        catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->authRoleTemplate->apply($postData);
    }

    public function applyManagers()
    {
        $postData = json2arr(input());
        try { $this->validate($postData, $this->validatePath . 'AuthRoleTemplateApplyManagers'); }
        catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->authRoleTemplate->applyManagers($postData);
    }

    public function getManagers()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'AuthRoleTemplateManagers'); }
        catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->authRoleTemplate->getManagers($postData);
    }

    protected function getExcludedTemplateNodeIds()
    {
        if (!in_array($this->manager['ao_id'], $this->getTopOrgIds())) return [];
        $topNodeIds = Db::name('auth_node')
            ->where(['pid' => 0])
            ->whereRaw("name like '%系统管理%' or name like '%更新日志%'")
            ->column('node_id');
        if (!$topNodeIds) return [];

        $excluded = array_map('intval', $topNodeIds);
        $nodes = Db::name('auth_node')->field('node_id,pid')->select()->toArray();
        do {
            $count = count($excluded);
            foreach ($nodes as $node) {
                if (in_array(intval($node['pid']), $excluded, true)) {
                    $excluded[] = intval($node['node_id']);
                }
            }
            $excluded = array_values(array_unique($excluded));
        } while (count($excluded) > $count);
        return $excluded;
    }
}
