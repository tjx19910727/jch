<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 11:01
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityFullDec extends Common
{
    protected $validatePath = 'app\management\validate\VActivity.';

    /**
     * 查询满减活动列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,
            ["store_name" => "like","terminal_no" => "like","activity_name" => "like","start_date" => "between","end_date" => "between"]
        );
        return $this->app->activityFullDec->getList($where,$postData['pageNum'] ?? 0);
    }

    /**
     * 查询一条满减活动（详情）
     * @return array|string
     */
    public function getDetails()
    {
        $afd_id = input('afd_id');
        return $this->app->activityFullDec->getDetails(['afd_id' => $afd_id]);
    }

    /**
     * 添加满减活动
     * @return array|string
     */
    public function add()
    {
        $postData = input();
        actionLog($postData,"添加限时折扣活动");
        try {
            $this->validate($postData, $this->validatePath . "addFullDec");
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityFullDec->addInfo($postData);
    }

    /**
     * 修改满减活动主体信息
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . "updateFullDec");
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityFullDec->update($postData);
    }

    /**
     * 删除满减活动，同步删除时间段与商品关联列表
     * @return bool|string
     */
    public function del()
    {
        $afd_id = input('afd_id');
        return $this->app->activityFullDec->delInfo($afd_id);
    }
}