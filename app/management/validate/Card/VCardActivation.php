<?php
/**
 * 卡激活活动校验
 */

namespace app\management\validate\Card;

use app\management\validate\VCommon;

class VCardActivation extends VCommon
{
    protected $rule = [
        'id' => 'require',
        'acd_id' => 'require',
        'pick_name' => 'require',
        'money' => 'require|float|egt:0',
        'file_path' => 'require',
    ];

    protected $message = [
        'id.require' => '活动ID不能为空',
        'acd_id.require' => '明细ID不能为空',
        'pick_name.require' => '活动名称不能为空',
        'money.require' => '赠送金额不能为空',
        'money.float' => '赠送金额格式错误',
        'money.egt' => '赠送金额不能小于0',
        'file_path.require' => '请先上传导入文件',
    ];

    protected $scene = [
        'add' => ['pick_name', 'money'],
        'update' => ['id', 'pick_name', 'money'],
        'del' => ['id'],
        'getFind' => ['id'],
        'getDetailList' => ['id'],
        'delDetail' => ['acd_id'],
        'import' => ['id', 'file_path'],
    ];
}
