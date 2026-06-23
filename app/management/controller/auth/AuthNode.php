<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 9:47
 */

namespace app\management\controller\auth;


use app\management\controller\Common;
use think\facade\Db;

class AuthNode extends Common
{
    protected $validatePath = 'app\management\validate\VAuth.';

    /**
     * 查询一条节点信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $field = "node_id,pid,name,icon,url,desc,sort,type,data_auth,permission_action,is_auth,is_button,status";
        return $this->app->authNode->getFind($where,$field);
    }

    /**
     * 获取节点列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,['name' => "like",'url' => 'like']);
        $top_org_ids = $this->getTopOrgIds();
        if(in_array($this->manager['ao_id'], $top_org_ids)){
            $nodeWhereRaw = " name like '%系统管理%' or name like '%更新日志%'";
            $nodeWhere['pid'] = 0;
            $unShowNodes = Db::name('auth_node')->where($nodeWhere)->whereRaw($nodeWhereRaw)->column('node_id');
            $children = $this->getChildAuthNode($unShowNodes);
            $where[] = ['node_id', 'not in', $children];
        }
        $field = "node_id,pid,name,icon,url,desc,sort,type,data_auth,permission_action,is_auth,is_button,status";
        $result = $this->app->authNode->getList($where,$pageNum,$field);
        return $result;
    }

    /**
     * 添加节点
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'AuthNodeAdd');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->authNode->add($postData);
        return $result;
    }

    /**
     * 修改节点
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'AuthNodeUpdate');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->authNode->update($postData);
        return $result;
    }

    /**
     * 删除节点
     * @return mixed
     */
    public function del()
    {
        $postData = input();
        return $this->app->authNode->delNode($postData['node_id']);
    }
}
