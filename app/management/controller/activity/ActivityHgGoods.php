<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/7
 * Time: 14:04
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityHgGoods extends Common
{
    protected $validatePath = 'app\management\validate\VActivity.';

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . "addHgG");
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityHgGoods->addInfo($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . "updateHgGoods");
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityHgGoods->update($postData);
    }

    public function del()
    {
        $ahg_id = input('ahg_id');
        return $this->app->activityHgGoods->del(['ahg_id' => $ahg_id]);
    }
}