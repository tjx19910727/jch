<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/9
 * Time: 10:45
 */

namespace app\management\controller\resource;


use app\management\controller\Common;

class Resource extends Common
{

    protected $field = "res_id,title,file_path,type,file_name,`desc`,length,width,size,`status`,ao_id,creator,create_time,update_id,update_time";
    protected $validatePath = 'app\management\validate\VResource.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["title" => "like","desc" => "like"]);
        return $this->app->resource->getList($where,$pageNum,$this->field,"res_id desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->resource->getFind($where,$this->field,'res_id desc');
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->resource->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $postData['status'] = 2;
        return $this->app->resource->update($postData);
    }


    /**
     * 审核启用素材
     * @return array|mixed|string
     */
    public function auditRes()
    {
        $res_id = input('res_id');
        $status = input('status',1);
        if (!$res_id) return returnState(100,lang("VResource.res_id_require"));
        return $this->app->resource->update(['res_id' => $res_id,'status' => $status],[],['status']);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->resource->del($postData);
    }
}