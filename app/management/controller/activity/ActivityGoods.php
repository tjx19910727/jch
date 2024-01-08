<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/9
 * Time: 13:54
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityGoods extends Common
{
    protected $validatePath = 'app\management\validate\VActivity.';

    /**
     * 获取活动商品列表
     * @return mixed
     */
    public function getList()
    {
        $id = input('a_id');
        $a_type  = input('a_type');
        $where['a_id'] = $id;
        $where['a_type'] = $a_type;
        $pageNum = input('pageNum',0);
        return $this->app->activityGoods->getList($where,$pageNum,"ag_id,a_type,a_id,store_id,ss_id,shelves_number,wg_id,goods_id,goods_name,goods_pic,goods_c_id,goods_c_name");
    }

    /**
     * 添加活动商品信息
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        return $this->app->activityGoods->addInfo($postData);
    }

    /**
     * 修改活动商品信息
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData,$this->validatePath. "updateGoods");
        } catch (\Exception $e) {
            actionException($e, 1);
            return returnValidate($e->getMessage());
        }
        return $this->app->activityGoods->update($postData);
    }

    /**
     * 删除活动商品信息
     * @return mixed
     */
    public function del()
    {
        $id = input('ag_id');
        $where['ag_id'] = $id;
        return $this->app->activityGoods->del($where);
    }
}