<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 17:53
 */

namespace app\AppFactory\Kernel\Traits\Config;


use app\AppFactory\Kernel\Model\Config\ConfigModel;

trait ConfigTrait
{
    protected $systemInfo = [];
    public function getConfigFind($where,$field = "*",$order = "")
    {
        return ConfigModel::getFind($where,$field,$order);
    }

    public function getConfigList($where,$pageNum = 0, $field = "*",$order = "")
    {
        return ConfigModel::getList($where,$pageNum,$field,$order);
    }

    public function addConfig($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $config = ConfigModel::create($insert);
        return $config->config_id;
    }

    public function updateConfig($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        return ConfigModel::update($update,$where,$field);
    }

    /**
     * 获取配置内容
     * @param $where
     * @return ConfigTrait|array|mixed|null|\think\Model
     */
    public function getConfigContent($where)
    {
        $config = $this->getConfigFind($where,'*','config_id desc');
        if ($config) {
            $config = json2arr($config['config_content']);
        }
        return $config;
    }

    /**
     * 获取系统配置信息
     * @return array
     */
    public function getSystemInfo()
    {
        $where['config_name'] = "systemInfo";
        $where['config_switch'] = 1;
        $systemInfo = $this->getConfigContent($where);
        $this->systemInfo = $systemInfo;
        return $systemInfo;
    }

    /**
     * 获取链接
     * @param $path
     * @return string
     */
    public function getUrl($path = []):string
    {
        $this->systemInfo ? : $this->getSystemInfo();
        $url = ($this->systemInfo['domain_name'] ?? $_SERVER["HTTP_HOST"]) . $path;
        return $url;
    }

}