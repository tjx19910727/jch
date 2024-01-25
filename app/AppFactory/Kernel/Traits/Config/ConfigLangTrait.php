<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 17:06
 */

namespace app\AppFactory\Kernel\Traits\Config;


use app\AppFactory\Kernel\Model\Config\ConfigLangModel;

trait ConfigLangTrait
{
    public function getConfigLangFind($where,$field = "*",$order = "")
    {
        return ConfigLangModel::getFind($where,$field,$order);
    }

    public function getConfigLangList($where,$pageNum = 0,$field = "*",$order = "create_time desc")
    {
        return ConfigLangModel::getList($where,$pageNum,$field,$order);
    }

    public function addConfigLang($insert)
    {
        if (isset($this->manager['manager_id'])) $insert['creator'] = $this->manager['manager_id'];
        $lang = ConfigLangModel::create($insert);
        return $lang->l_id;
    }

    public function updateConfigLang($update,$where = [],$field = [])
    {
        return ConfigLangModel::update($update,$where,$field);
    }

    public function delConfigLang($where)
    {
        return ConfigLangModel::whereDel($where);
    }
}