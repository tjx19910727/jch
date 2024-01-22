<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 14:47
 */

namespace app\management\controller\advertisement;


use app\management\controller\Common;

class AdvertisementPush extends Common
{
    protected $validatePath = 'app\management\validate\VAdvertisement.';

    /**
     * 获取广告推送列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['adv_title' => "like"]);
        return $this->app->advertisementPush->getList($where,($postData['pageNum'] ?? 0));
    }

    /**
     * 广告推送
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        $postData = json2arr($postData);
        try { $this->validate($postData,$this->validatePath . 'addPush');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->advertisementPush->addMorePush($postData);
    }

    /**
     * 修改广告推送数据
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'updatePush');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->advertisementPush->updatePush($postData);
    }

    /**
     * 删除广告推送
     * @return mixed
     */
    public function del()
    {
        $id = input('adv_id');
        strpos($id,",") === false ? $where['adv_id'] = $id : $where[] = ['adv_id','in',$id];
        return $this->app->advertisementPush->del($where);
    }

    /**
     * 上架下架广告
     * @return array|string
     */
    public function upDown()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'upDown');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->advertisementPush->upDown($postData);
    }
}