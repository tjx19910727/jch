<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/25
 * Time: 15:45
 */

namespace app\management\controller\auth;


use app\management\controller\Common;

class AuthManagerLog extends Common
{

    protected $field = "*";

    /**
     * 获取用户事件列表
     * @return array|\think\response\Json
     */
    public function getList()
    {
        $postData = input();
        $userKeyword = $postData['user_keyword'] ?? '';
        if (isset($postData['user_keyword'])) unset($postData['user_keyword']);
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["nickname" => "like", "account" => "like", 'params' => 'like']);
        if ($userKeyword !== '') {
            $pathList = $this->getPathListByKeyword($userKeyword);
            if ($pathList) {
                $where[] = ['path', 'in', $pathList];
            } else {
                $where[] = ['ml_id', '=', -1];
            }
        }
        return $this->app->authManagerLog->getMlList($where,$pageNum,$this->field,'create_time desc');
    }

    /**
     * 根据事件中文名模糊匹配对应的 path 列表
     * @param string $keyword
     * @return array
     */
    private function getPathListByKeyword($keyword)
    {
        $pathList = [];

        $nodeList = $this->app->authNode->getAuthNodeList([], 0, 'node_id,pid,url,name');
        if ($nodeList) {
            $nodeList = $nodeList->toArray();
            $nodeMap = [];
            foreach ($nodeList as $node) {
                if (!isset($node['node_id'])) continue;
                $nodeMap[$node['node_id']] = $node;
            }

            foreach ($nodeList as $node) {
                if (!isset($node['url']) || !$node['url']) continue;

                $nameList = [];
                $currentNode = $node;
                $maxDeep = 20;
                while ($currentNode && $maxDeep > 0) {
                    if (isset($currentNode['name']) && $currentNode['name'] !== '') {
                        array_unshift($nameList, $currentNode['name']);
                    }
                    if (!isset($currentNode['pid']) || !$currentNode['pid'] || !isset($nodeMap[$currentNode['pid']])) {
                        break;
                    }
                    $currentNode = $nodeMap[$currentNode['pid']];
                    $maxDeep--;
                }

                $fullName = implode('-', $nameList);
                if (stripos($fullName, $keyword) !== false || stripos($node['name'], $keyword) !== false) {
                    $pathList[] = $node['url'];
                }
            }
        }

        // $otherPath = config('auth_manager_log_list.otherPath');
        // if ($otherPath) {
        //     foreach ($otherPath as $item) {
        //         if (!isset($item['name']) || !isset($item['url'])) continue;
        //         if (stripos($item['name'], $keyword) !== false) {
        //             $pathList[] = $item['url'];
        //         }
        //     }
        // }

        return array_values(array_unique(array_filter($pathList)));
    }

    /**
     * 导出用户事件
     * @return array|\think\response\Json
     */
    public function exportAul()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["nickname" => "like","account" => "like"]);
        if (!isset($where['create_time']) || !$where['create_time']) $where[] = ['create_time','between',[strtotime("-1 months"),time()]];
        $field = "ml_id,ao_id,nickname,account,path,params,(CASE position WHEN 1 THEN '管理后台' WHEN 2 THEN '终端' WHEN 3 THEN '手机端' END) position,FROM_UNIXTIME(create_time) create_time";
        return $this->app->authManagerLog->exportAul($where,$field,"create_time desc");
    }
}