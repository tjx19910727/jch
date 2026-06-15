<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:41
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityPickCode extends Common
{

    protected $field = "apc_id,ap_id,code,trade_no,machine_id,machine_name,
    (CASE pick_type WHEN 1 THEN '系统随机' WHEN 2 THEN '用户自选' WHEN 3 THEN '预售订单' END) pick_type,
    (CASE status WHEN 1 THEN '未使用' WHEN 2 THEN '已使用' WHEN 3 THEN '已过期' WHEN 4 THEN '已作废' WHEN 5 THEN '使用中' END) status,
    used_time";
    protected $validatePath = 'app\management\validate\Activity\VActivityPickCode.';

    /**
     * 获取提货码使用记录列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'getList');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->activityPickCode->getList($where,$pageNum,$this->field,'apc_id desc');
    }

    /**
     * 获取一条提货码使用记录
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->activityPickCode->getFind($where,$this->field);
    }

    /**
     * 生成提货码列表
     * @return array|string
     * @throws \Exception
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityPickCode->addMore($postData);
    }

    /**
     * 生成批量取货excel
     * @return boolen
     * @throws \Exception
     */
    public function addExcel()
    {
        return $this->app->activityPickCode->addExcel();
    }

    /**
     * 导入提货码列表
     * @return array|string
     * @throws \Exception
     */
    public function importAdd()
    {
        $postData = input();
        try {
            // $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityPickCode->importAdd($postData);
    }

    /**
     * 修改提货码使用记录
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityPickCode->update($postData);
    }

    /**
     * 删除提货码使用记录
     * @return array|mixed|string
     */
    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityPickCode->del($postData);
    }

    /**
     * 导出提货码
     * @return array|string
     */
    public function exportCode()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'export');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityPickCode->exportCode($postData);
    }

    /**
     * 导出提货码使用报表
     * @return array|string
     */
    public function exportUsedList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'export');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityPickCode->exportUsedList($postData);
    }

    /**
     * 核销取货码
     * @return array|bool|string|\think\response\Json
     */
    public function usePickCode()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'usePickCode');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityPickCode->usePickCode($postData);
    }
}
