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

    /**组织租赁设备
     * 每次执行，先将组织原租赁设备记录删除，增加新记录
     */
    public  function orgRentMachine()
    {
        $postData = input();
        $addData['ao_id'] = $postData['ao_id'] ?? '';
        // $addData['m_id'] = $postData['m_id'] ?? '';
        $m_ids_raw = $postData['m_ids'] ?? '';
        if (!$m_ids_raw) return returnState(100, 'm_ids不能为空');
        // 支持逗号分隔的多个 machine_id 或者数组 JSON
        if (is_array($m_ids_raw)) {
            $m_ids = $m_ids_raw;
        } else {
            $m_ids = array_filter(array_map('trim', explode(',', $m_ids_raw)));
        }

        if (!$addData['ao_id']) return returnState(100, 'ao_id不能为空');

        // 开始事务：先删除组织原有的租赁记录，再新增新的记录
        $this->app->authOrganization->startTrans();
        try {
            // 删除当前组织的所有租赁记录
            $this->app->authOrganization->delAuthOrgMC(['ao_id' => $addData['ao_id']]);
            $inserted = [];
            foreach ($m_ids as $m_id) {
                $machine_id = $this->app->machine->getMachineValue(['m_id' => $m_id], 'machine_id');
                $res = $this->app->authOrganization->addAuthOrgMC([
                    'ao_id' => $addData['ao_id'],
                    'm_id' => $m_id,
                    'machine_id' => $machine_id
                ]);
                $inserted[] = $res;
            }
            return $this->app->authOrganization->checkTrans($inserted ?? true);
        } catch (\Exception $e) {
            $this->app->authOrganization->rollbackTrans();
            actionException($e, 1);
            return $this->app->authOrganization->rValidate($e->getMessage());
        }
    }


    public function getAuthOrgRentMachineLists(){
        $postData = input();
        $pageNum = $postData['pageNum'] ?? '';
        $where = $this->getWhere($postData, false, []);
        return $this->app->authOrganization->getAuthOrgMCLists($where, $pageNum);
    }
    
}
 