<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 9:27
 */

namespace app\management\controller\wx;


use app\management\controller\Common;

class WxTemplateLog extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\V.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, [
            'template_name' => 'like',
            'nickname' => 'like',
            'openid' => 'like',
            'template_type' => 'like',
            'error_code' => 'like',
            'remark' => 'like',
            'create_time' => 'between',
        ]);
        return $this->app->wxTemplateLog->getTemplateLogList($where,$pageNum,$this->field,'create_time desc');
    }

}
