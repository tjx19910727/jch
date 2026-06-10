<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:23
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthManagerRoleModel;
use think\facade\Db;

trait AuthManagerRoleTrait
{

    public function getAuthManagerRoleFind($where,$field = "*", $order = "")
    {
        $where['is_del'] = 2;
        return AuthManagerRoleModel::getFind($where,$field,$order);
    }

    public function getAuthManagerRoleColumn($where,$column)
    {
        return AuthManagerRoleModel::getColumn($where,$column);
    }

    public function getAuthManagerRoleList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $where['is_del'] = 2;
        return AuthManagerRoleModel::getJoinRoleList($where,$pageNum,$field,$order);
    }

    public function addAuthManagerRole($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $role_id = explode(",",$insert['role_id']);
        foreach ($role_id as $key => $value) {
            $insert['role_id'] = $value;
            $data = AuthManagerRoleModel::create($insert);
            $return[] = $data->mr_id;
        }
        return $return;
    }

    /**
     * 按账号配置解析角色节点。未开启模板时完整保留历史 auth_role_node 逻辑。
     * 开启模板后，已关联模板的角色读取模板节点，未关联模板的角色仍读取历史节点。
     */
    public function resolveManagerRoleNodes(array $manager, array $roleIds, $nodeId = 0)
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds))));
        if (!$roleIds) return [];

        $useTemplate = intval($manager['use_role_template'] ?? 2) === 1;
        $templateRoleIds = [];
        $historyRoleIds = $roleIds;
        if ($useTemplate) {
            $templateRoleIds = Db::name('auth_role')
                ->alias('ar')
                ->join('auth_role_template art', 'art.art_id = ar.template_id')
                ->where('ar.role_id', 'in', $roleIds)
                ->where('ar.template_id', '>', 0)
                ->where(['art.status' => 1, 'art.is_del' => 2])
                ->column('ar.role_id');
            $templateRoleIds = array_map('intval', $templateRoleIds);
            $historyRoleIds = array_values(array_diff($roleIds, $templateRoleIds));
        }

        $nodes = [];
        if ($historyRoleIds) {
            $query = Db::name('auth_role_node')
                ->where('role_id', 'in', $historyRoleIds)
                ->where('is_del', 2);
            if ($nodeId > 0) $query->where('node_id', intval($nodeId));
            $nodes = $query->field('node_id,d_type')->select()->toArray();
        }
        if ($templateRoleIds) {
            $query = Db::name('auth_role_template_node')
                ->alias('artn')
                ->join('auth_role ar', 'ar.template_id = artn.art_id')
                ->where('ar.role_id', 'in', $templateRoleIds)
                ->where('artn.is_del', 2);
            if ($nodeId > 0) $query->where('artn.node_id', intval($nodeId));
            $nodes = array_merge($nodes, $query->field('artn.node_id,artn.d_type')->select()->toArray());
        }

        $result = [];
        foreach ($nodes as $node) {
            $id = intval($node['node_id']);
            $dType = intval($node['d_type']);
            if (!isset($result[$id]) || $dType < $result[$id]['d_type']) {
                $result[$id] = ['node_id' => $id, 'd_type' => $dType];
            }
        }
        return array_values($result);
    }

    public function resolveManagerRoleNodeIds(array $manager, array $roleIds)
    {
        return array_column($this->resolveManagerRoleNodes($manager, $roleIds), 'node_id');
    }

    public function updateAuthManagerRole($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        return AuthManagerRoleModel::update($update,$where,$field);
    }

    public function delAuthManagerRole($where)
    {
        return AuthManagerRoleModel::whereDel($where);
    }

    /**
     * 获取账号权限节点
     * @param array $manager 账号数据
     * @param string $fatherNodeUrl 父节点URL
     * @return mixed
     */
    public function getManagerNodeList($manager,$fatherNodeUrl = "machine")
    {
        $roleIds = $this->getAuthManagerRoleColumn([
            'manager_id' => $manager['manager_id'],
            'is_del' => 2,
        ], 'role_id');
        // 查询权限根节点
        $machineNodeId = $this->getAuthNodeValue(['url' => $fatherNodeUrl],'node_id');
        if (!$machineNodeId) return $this->r(100,$this->lang("VLogin.permission_denied"));
        // 查询权限所有子节点
        $nodeTree = $this->getAuthNodeChildIdList($machineNodeId);
        $nodeTree[] = $machineNodeId;
        // 非超管
        if ($manager['pid'] > 0) {
            $nodeIds = array_intersect($nodeTree, $this->resolveManagerRoleNodeIds($manager, $roleIds));
            $nodeList = $this->getAuthNodeList([
                ['node_id', 'in', $nodeIds],
                'status' => 1,
            ], 0, 'node_id,pid,name,desc,url', 'node_id asc', 'node_id');
        } else {
            $nodeList = $this->getAuthNodeList([['node_id','in',$nodeTree]],0,'node_id,pid,name,desc,url','node_id asc',"node_id");
        }
        return $nodeList;
    }
}
