<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/8/19
 * Time: 10:00
 */

namespace app\management\validate\Machine;

use app\management\validate\VCommon;

class VMachineRemark extends VCommon
{
    protected $rule = [
        'id' => 'require',
        'm_id' => 'require|gt:0',
        'type' => 'in:0,1,2',
        'remark' => 'require|max:65535',
    ];

    protected $message = [
        'id.require' => 'id不能为空',
        'm_id.require' => '设备不能为空',
        'm_id.gt' => '设备参数错误',
        'type.in' => '备注类型不合法',
        'remark.require' => '备注内容不能为空',
        'remark.max' => '备注内容过长',
    ];

    protected $scene = [
        'add' => ['m_id', 'type', 'remark'],
        'update' => ['id', 'm_id', 'type', 'remark'],
        'del' => ['id'],
    ];
}
