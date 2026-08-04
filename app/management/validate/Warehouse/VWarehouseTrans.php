<?php

namespace app\management\validate\Warehouse;

use app\management\validate\VCommon;

class VWarehouseTrans extends VCommon
{
    protected $rule = [
        'type' => 'require|integer|in:1,2,3,4',
        'record_no' => 'max:32',
        'material_manager_id' => 'require|integer|gt:0',
        'business_at' => 'date',
        'remark' => 'max:500',
    ];

    protected $message = [
        'type.require' => '仓库变化类型不能为空',
        'type.integer' => '仓库变化类型格式错误',
        'type.in' => '仓库变化类型仅支持1、2、3、4',
        'record_no.max' => '预补货单号长度不能超过32',
        'record_no.require' => '预补货单号不能为空',
        'material_manager_id.require' => '物料操作人不能为空',
        'material_manager_id.integer' => '物料操作人格式错误',
        'material_manager_id.gt' => '物料操作人格式错误',
        'business_at.date' => '业务发生时间格式错误',
        'remark.max' => '备注长度不能超过500',
    ];

    protected $scene = [
        'add' => ['type', 'record_no', 'material_manager_id', 'business_at', 'remark'],
        'getPreReplenishmentGoodsList' => ['record_no' => 'require|max:32'],
    ];
}
