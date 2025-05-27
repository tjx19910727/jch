<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 10:36
 */

namespace app\management\controller\email;


use app\management\controller\Common;

class EmailTemplateLog extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\V.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->emailTemplateLog->getList($where,$pageNum,$this->field,'etl_id desc');
    }
}