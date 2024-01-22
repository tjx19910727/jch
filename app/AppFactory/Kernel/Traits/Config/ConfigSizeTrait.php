<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/9
 * Time: 11:08
 */

namespace app\AppFactory\Kernel\Traits\Config;


use app\AppFactory\Kernel\Model\Config\ConfigSizeModel;

trait ConfigSizeTrait
{
    public function getConfigSizeFind($where,$field = "*",$order = "")
    {
        return ConfigSizeModel::getFind($where,$field,$order);
    }

    public function getConfigSizeList($where,$pageNum = 0, $field = "*",$order = "")
    {
        return ConfigSizeModel::getList($where,$pageNum,$field,$order);
    }

    public function addConfigSize($insert)
    {
        $size = ConfigSizeModel::create($insert);
        return $size->s_id;
    }

    public function updateConfigSize($update, $where = [], $field = [])
    {
        return ConfigSizeModel::update($update,$where,$field);
    }

    public function delConfigSize($where)
    {
        return ConfigSizeModel::destroy($where);
    }
}