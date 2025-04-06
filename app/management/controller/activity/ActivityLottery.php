<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/19
 * Time: 16:50
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityLottery extends Common
{

    protected $field = "`al_id`,`lottery_name`,`start_time`,`end_time`,`price`,`position`,share_benefit,`desc`,`status`,`creator`,`create_time`";
    protected $validatePath = 'app\management\validate\Activity\VActivityLottery.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['lottery_name' => "like"]);
        return $this->app->activityLottery->getList($where,$pageNum,$this->field,'al_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->activityLottery->getAlFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityLottery->addAl($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityLottery->updateAl($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityLottery->delAl($postData);
    }

    /**
     * 抽奖活动主动下架
     * @return array|bool|string
     */
    public function takeDown()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'takeDown');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        if (strpos($postData['al_id'],",") !== false) $where[] = ['al_id',"in",$postData['al_id']];
        else $where['al_id'] = $postData['al_id'];
        return $this->app->activityLottery->alTakeDown($where);
    }

}