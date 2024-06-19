<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 16:25
 */

namespace app\management\controller\config;


use app\management\controller\Common;
use app\management\validate\Config\VConfigApi;

class ConfigApi extends Common
{

    protected $field = "*";
    protected $validatePath = VConfigApi::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["auth_name" => "like","white_list" => "like"]);
        return $this->app->configApi->getList($where,$pageNum,$this->field,'create_time desc');
    }

    public function getFind()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.getFind');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $where = $this->getWhere($postData, false, []);
        return $this->app->configApi->getFind($where,$this->field,'create_time desc');
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->configApi->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->configApi->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->configApi->del($postData);
    }
}