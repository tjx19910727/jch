<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/11/11
 * Time: 11:30
 */

namespace app\AppFactory\Management\Action;


use app\AppFactory\Kernel\Traits\Action\ActionVideoTrait;
use app\AppFactory\Management\ManagementClient;

class ActionVideoClient extends ManagementClient
{
    use ActionVideoTrait;

    public function getTagList()
    {
        $tagList = $this->getActionVideoColumn([],'tag');
        if ($tagList) {
            $tagList = implode(",", $tagList);
            $tagList = explode(",", $tagList);
            $tagList = array_unique($tagList);
        }
        return $this->r(200,$this->lang("query_success"),['tag' => $tagList]);
    }

}