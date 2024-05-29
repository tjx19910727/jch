<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 17:56
 */

namespace app\management\controller\config;


use app\management\controller\Common;
use app\management\validate\VConfig;

class Config extends Common
{

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->config->getParentConfigFind($where,"*",'config_id desc');
    }

    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['config_name' => 'like']);
        return $this->app->config->getList($where,$postData['pageNum'] ?? 0);
    }

    public function add()
    {
        $postData = input();
        try { $this->validate($postData,VConfig:: class. '.add');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->config->add($postData);
    }

    /**
     * 修改系统配置
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,VConfig::class . '.update');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        if (isset($postData['config_content'])) {
            $content = json2arr($postData['config_content']);
            if ($postData['config_name'] == "fluorite") {
                $checkContent = $this->validate($content,VConfig::class . ".fluorite");
                if (!$checkContent) return returnValidate("无对应配置可以修改");
                if ($checkContent !== true) return returnValidate($checkContent);
            }
        }
        return $this->app->config->updateC($postData);
    }
}