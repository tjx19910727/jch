<?php

namespace app\management\validate\Currency;

use app\management\validate\VCommon;

class VCurrency extends VCommon
{
    protected $rule = [
        'currency_code' => 'require|regex:/^[A-Za-z]{3}$/',
        'currency_name' => 'require|max:32',
        'currency_symbol' => 'require|max:8',
        'decimal_places' => 'integer|between:0,3',
        'status' => 'in:1,2',
        'is_default' => 'in:0,1',
        'sort' => 'integer|>=:0',
    ];

    protected $message = [
        'currency_code.require' => '币种编码不能为空',
        'currency_code.regex' => '币种编码必须为三位字母',
        'currency_name.require' => '币种名称不能为空',
        'currency_symbol.require' => '币种符号不能为空',
        'decimal_places.between' => '小数位必须为0至3',
        'status.in' => '币种状态无效',
        'is_default.in' => '默认币种参数无效',
    ];

    protected $scene = [
        'add' => ['currency_code', 'currency_name', 'currency_symbol', 'decimal_places', 'status', 'is_default', 'sort'],
        'update' => ['currency_code', 'currency_name', 'currency_symbol', 'decimal_places', 'status', 'is_default', 'sort'],
    ];

    public function sceneUpdate()
    {
        return $this->only(['currency_code', 'currency_name', 'currency_symbol', 'decimal_places', 'status', 'is_default', 'sort'])
            ->remove('currency_name', 'require')
            ->remove('currency_symbol', 'require');
    }
}
