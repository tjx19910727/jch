<?php

namespace app\management\controller\auth;

use app\management\controller\Common;

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

    public function apply()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'AuthRoleTemplateApply'); }
        catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->authRoleTemplate->apply($postData);
    }
}
