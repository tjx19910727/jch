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
        return $this->app->authOrganization->getFind($where, $field);
    }

    /**
     * 获取组织架构列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['organization_name' => "like"]);
        $field = "ao_id,pid,level,organization_name,sort,creator, create_time";
        $result = $this->app->authOrganization->getList($where, $pageNum, $field, 'level asc,sort asc');
        return $result;
    }


    /**
     * 添加组织架构
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'AuthOrganizationAdd');
        } catch (\Exception $e) {
            return returnValidate(Lang::get($e->getMessage()));
        }
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
        try {
            $this->validate($postData, $this->validatePath . 'AuthOrganizationUpdate');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
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
        try {
            $this->app->authManager->updateAuthManager(["ao_id" => 0], ["ao_id" => $postData['ao_id']]);
            $flag[] = $this->app->authOrganization->delAuthOrganization(['ao_id' => $postData['ao_id']]);
            $result = flag_check($flag);
            return $this->app->authOrganization->checkTrans($result);
        } catch (\Exception $e) {
            $this->app->authOrganization->rollbackTrans();
            actionException($e, 1);
            return $this->app->authOrganization->rValidate($e->getMessage());
        }
    }

    //组织关联设备货道  channel_code 为逗号分隔的字符串
    public  function addMachineChannel()
    {
        $postData = input();
        $addData['ao_id'] = $postData['ao_id'] ?? '';
        // id($addData['ao_id'] < 18) return 
        $addData['m_id'] = $postData['m_id'] ?? '';
        $addData['machine_id'] = $postData['machine_id'] ?? '';
        $channel_code = $postData['channel_code'] ?? '';
        $channel_code = explode(',', $channel_code);
        $addData['channel_code'] = json_encode($channel_code);
        $res = $this->app->authOrganization->addAuthOrgMC($addData);
        $machine_channel_where['m_id'] = $addData['m_id'];
        $machine_channel_where['machine_id'] = $addData['machine_id'];
        $machine_channel_where[] = [['channel_code', 'in', $channel_code]];
        $this->app->machine->updateMachineChannel(['ao_id' => $addData['ao_id']], $machine_channel_where);
        return returnData($res);
    }

    //组织修改设备货道信息  channel_code 为逗号分隔的字符串
    public function  updateMachineChannel()
    {

        $postData = input();
        $updateData['ao_id'] = $postData['ao_id'] ?? '';
        $updateData['m_id'] = $postData['m_id'] ?? '';
        $updateData['machine_id'] = $postData['machine_id'] ?? '';
        $channel_code = $postData['channel_code'] ?? '';
        $channel_code = explode(',', $channel_code);
        $updateData['channel_code'] = json_encode($channel_code);
        $res = $this->app->authOrganization->updateAuthOrgMc($updateData, ['id' => $postData['id']]);
        $this->app->machine->updateMachineChannel(['ao_id' => 1], ['m_id' => $updateData['m_id']]);
        $machine_channel_where['m_id'] = $updateData['m_id'];
        $machine_channel_where['machine_id'] = $updateData['machine_id'];
        $machine_channel_where[] = [['channel_code', 'in', $channel_code]];
        $this->app->machine->updateMachineChannel(['ao_id' => $updateData['ao_id']], $machine_channel_where);
        return returnData($res);
    }
}
