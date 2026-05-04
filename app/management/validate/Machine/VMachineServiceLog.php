<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/5/4
 * Time: 10:04
 */

namespace app\management\validate\Machine;

use app\management\validate\VCommon;

class VMachineServiceLog extends VCommon
{
    protected $rule = [
        'id' => 'require',
        'm_id' => 'requireWithout:machine_id',
        'machine_id' => 'requireWithout:m_id',
        'name' => 'require|max:255',
        'path' => 'require|max:255',
        'date' => 'require|dateFormat:Y-m-d',
        'remark' => 'max:65535',
    ];

    protected $message = [
        'id.require' => 'id不能为空',
        'm_id.requireWithout' => 'm_id和machine_id不能同时为空',
        'machine_id.requireWithout' => 'machine_id和m_id不能同时为空',
        'name.require' => '文件名称不能为空',
        'name.max' => '文件名称长度不能超过255',
        'path.require' => '文件路径不能为空',
        'path.max' => '文件路径长度不能超过255',
        'date.require' => '日志日期不能为空',
        'date.dateFormat' => '日志日期格式错误，应为YYYY-MM-DD',
        'remark.max' => '备注内容过长',
    ];

    protected $scene = [
        'add' => ['m_id', 'machine_id', 'name', 'path', 'date', 'remark'],
        'update' => ['id', 'm_id', 'machine_id', 'name', 'path', 'date', 'remark'],
        'del' => ['id'],
        'getMachineServiceLog' => ['m_id', 'machine_id', 'date'],
    ];
}
