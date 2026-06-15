<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/5/15
 * Time: 10:41
 */

namespace app\management\validate\Machine;

use app\management\validate\VCommon;

class VMachineMaintenance extends VCommon
{
    protected $rule = [
        'id' => 'require',
        'item_name' => 'require|max:255',
        'parent_id' => 'integer|egt:0',
        'cycle_days' => 'integer|egt:0',
        'description' => 'max:65535',
        'sort_order' => 'integer',
        'is_active' => 'in:0,1',
    ];

    protected $message = [
        'id.require' => 'id不能为空',
        'item_name.require' => '项目名称不能为空',
        'item_name.max' => '项目名称长度不能超过255',
        'parent_id.integer' => '父级ID格式错误',
        'parent_id.egt' => '父级ID不能小于0',
        'cycle_days.integer' => '维护周期必须为整数',
        'cycle_days.egt' => '维护周期不能小于0',
        'description.max' => '描述内容过长',
        'sort_order.integer' => '排序值必须为整数',
        'is_active.in' => '启用状态仅支持0或1',
    ];

    protected $scene = [
        'add' => ['item_name', 'parent_id', 'cycle_days', 'description', 'sort_order', 'is_active'],
        'update' => ['id', 'item_name', 'parent_id', 'cycle_days', 'description', 'sort_order', 'is_active'],
        'setActive' => ['id', 'is_active'],
        'del' => ['id'],
    ];
}
