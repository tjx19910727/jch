<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 17:48
 */

namespace app\AppFactory\Management\Config;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;
use app\AppFactory\Management\ManagementClient;

class ConfigClient extends ManagementClient
{
    use ConfigTrait,AuthManagerTrait;

    public function getParentConfigFind($where,$field,$order = "")
    {
        $ids = $this->getParentIdList($this->manager['pid']);
        $ids[] = $this->manager['manager_id'];
        $where[] = ['creator',"in",$ids];
        return $this->rQ($this->getConfigFind($where,$field,$order));
    }
}