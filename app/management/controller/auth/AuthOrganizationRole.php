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

class AuthOrganizationRole  extends Common
{
    protected $validatePath = 'app\management\validate\VAuth.';

    /**
     * 查询一条组织架构关联权限角色信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $field = "or_id,ao_id,role_id,is_del,creator, create_time";
        return $this->app->authOrganizationRole->getFind($where,$field);
    }

    /**
     * 获取组织架构列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        if($postData['ao_id'] ?? false){
            $postData['or.ao_id'] = $postData['ao_id'];
            unset($postData['ao_id']);
        }
        if($postData['role_id'] ?? false){
            $postData['or.role_id'] = $postData['role_id'];
            unset($postData['role_id']);
        }
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false);
        $where['or.is_del'] = 2;
        $field = "or.or_id,or.ao_id,or.role_id,or.is_del,or.creator, or.create_time,ar.name";
        $result = $this->app->authOrganizationRole->getList($where,$pageNum,$field,'or.ao_id asc');
        return $result;
    }

    /**
     * 绑定/解绑关联权限角色
     * @return array|bool|string
     * @throws \Exception
     */
    public function bind()
    {
        $postData = input();
        $postData = json2arr($postData);
        try { $this->validate($postData,$this->validatePath . 'AuthOrganizationRoleBind');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->authOrganizationRole->bind($postData);
    }


}