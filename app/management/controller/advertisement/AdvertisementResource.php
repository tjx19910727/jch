<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 14:47
 */

namespace app\management\controller\advertisement;


use app\management\controller\Common;

class AdvertisementResource extends Common
{
    protected $validatePath = 'app\management\validate\VAdvertisement.';

    /**
     * 获取广告素材列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['title' => "like"]);
        $field = "res_id,title,file_path,type,file_name,desc,status";
        return $this->app->advertisementResource->getList($where,$postData['pageNum'] ?? 0,$field,"res_id desc");
    }

    /**
     * 添加广告素材
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'addRes');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->advertisementResource->add($postData);
    }

    /**
     * 修改广告素材
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'updateRes');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->advertisementResource->update($postData);
    }

    /**
     * 删除广告素材
     * @return array|mixed|string
     */
    public function del()
    {
        $id = input('res_id');
        $result = $this->app->advertisementResource->del(['res_id' => $id]);
        $check = obj2arr($result);
        if ($check['state'] == "200") {
            $wherePush['res_id'] = $id;
            if ($this->app->advertisementResource->getAdvertisementPushFind($wherePush)) {
                $updatePush['status'] = 5;
                $updateResult = $this->app->advertisementResource->updateAdvertisementPush($updatePush, $wherePush);
                return returnData($updateResult);
            }
        }
        return $result;
    }

}