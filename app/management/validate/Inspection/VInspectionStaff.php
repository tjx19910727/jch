<?php

namespace app\management\validate\Inspection;

use app\management\validate\VCommon;

class VInspectionStaff extends VCommon
{
    protected $rule = [
        'staff_id' => 'require|number',
        'staff_code' => 'regex:/^[1-9][0-9]{5}$/',
        'account_name' => 'require|max:100',
        'mobile' => 'mobile',
        'contact_address' => 'max:255',
        'expire_time' => 'require',
        'ao_id' => 'number',
        'status' => 'in:1,2',
        'remark' => 'max:500',
    ];

    protected $message = [
        'staff_id.require' => '巡检人员ID不能为空',
        'staff_id.number' => '巡检人员ID格式错误',
        'staff_code.regex' => '巡检账号必须为首位非0的6位数字',
        'account_name.require' => '账户名不能为空',
        'account_name.max' => '账户名长度不能超过100',
        'mobile.mobile' => '手机号格式不正确',
        'contact_address.max' => '联系地址长度不能超过255',
        'expire_time.require' => '过期时间不能为空',
        'ao_id.number' => '所属组织ID格式错误',
        'status.in' => '状态仅支持1启用或2禁用',
        'remark.max' => '备注长度不能超过500',
    ];

    protected $scene = [
        'add' => ['staff_code', 'account_name', 'mobile', 'contact_address', 'expire_time', 'ao_id', 'status', 'remark'],
    ];

    public function sceneUpdate()
    {
        return $this->only(['staff_id', 'staff_code', 'account_name', 'mobile', 'contact_address', 'expire_time', 'ao_id', 'status', 'remark'])
            ->remove('account_name', 'require')
            ->remove('expire_time', 'require');
    }
}
