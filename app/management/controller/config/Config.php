<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 17:56
 */

namespace app\management\controller\config;


use app\management\controller\Common;

class Config extends Common
{
    protected $validatePath = 'app\management\validate\VConfig.';

    public function getFind()
    {
        $config_id = input('config_id');
        return $this->app->config->getFind(['config_id' => $config_id]);
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
        try { $this->validate($postData,$this->validatePath . 'add');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $content = json2arr($postData['config_content']);
        $checkContent = "";
        if ($postData['config_name'] == "fluorite") $checkContent = $this->validate($content,$this->validatePath . "fluorite");
        if ($postData['config_name'] == "openPlatform") $checkContent = $this->validate($content,$this->validatePath . "openPlatform");
        if (!$checkContent) return returnValidate("无对应配置可以修改");
        if ($checkContent !== true) return returnValidate($checkContent);
        return $this->app->config->add($postData);
    }

    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'update');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        if (isset($postData['config_content'])) {
            $content = json2arr($postData['config_content']);
            if ($postData['config_name'] == "fluorite") {
                $checkContent = $this->validate($content,$this->validatePath . "fluorite");
                if (!$checkContent) return returnValidate("无对应配置可以修改");
                if ($checkContent !== true) return returnValidate($checkContent);
            }
        }
        return $this->app->config->update($postData);
    }
}