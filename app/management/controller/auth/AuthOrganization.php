<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/6
 * Time: 14:41
 */

namespace app\management\controller\auth;


use app\management\controller\Common;
use think\facade\Lang;

class AuthOrganization  extends Common
{
    protected $validatePath = 'app\management\validate\VAuth.';

    /**
     * 查询一条组织架构
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $field = "ao_id,pid,level,organization_name,sort,creator, create_time";
        return $this->app->authOrganization->getFind($where,$field);
    }

    /**
     * 获取组织架构列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,['organization' => "like"]);
        $field = "ao_id,pid,level,organization_name,sort,creator, create_time";
        $result = $this->app->authOrganization->getList($where,$pageNum,$field,'level asc,sort asc');
        return $result;
    }

    /**
     * 添加组织架构
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'AuthOrganizationAdd');} catch (\Exception $e) { return returnValidate(Lang::get($e->getMessage()));}
        $result = $this->app->authOrganization->add($postData);
        return $result;
    }

    /**
     * 修改组织架构
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'AuthOrganizationUpdate');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->authOrganization->update($postData);
        return $result;
    }

    /**
     * 删除组织架构
     * @return mixed
     */
    public function del()
    {
        $postData = input();
        $this->app->authOrganization->startTrans();
        $this->app->authManager->updateAuthManager(["ao_id" => 0],["ao_id" => $postData['ao_id']]);
        $flag[] = $this->app->authOrganization->del(['ao_id' => $postData['ao_id']],0);
        $result = flag_check($flag);
        return $this->app->authOrganization->checkTrans($result);
    }
}