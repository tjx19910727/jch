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
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["nickname" => "like", "account" => "like", 'params' => 'like']);
        return $this->app->authManagerLog->getMlList($where,$pageNum,$this->field,'create_time desc');
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