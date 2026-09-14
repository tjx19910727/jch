<?php

/**
 * 核心商品库 Excel 导入/导出标题的单一数据源。
 *
 * - 导出列标题与导入识别表头共用同一份定义，避免两端标题漂移（例如“型号”被导成“商品型号”导致回导失效）。
 * - columns    ：字段名 => 标准表头，既用于导出标题，也通过反转为导入识别表头。
 * - aliases    ：仅用于导入的兼容别名（旧模板、历史文件），导出不使用。
 * 调整列名/新增列只改这里即可；若只是兼容某历史导出文件，往 aliases 里加一行即可。
 */
return [

    // 标准列（顺序仅作阅读参考；导出列顺序由具体导出方法的字段列表决定）
    'columns' => [
        'g_name'            => '商品名称',
        'g_type'            => '商品类型',
        'gc_id'             => '分类ID',
        'gc_name'           => '商品分类',
        'model'             => '型号',
        'sku'               => 'SKU',
        'sku2'              => 'SKU2',
        'pic'               => '图片',
        'bar_code'          => '条形码',
        'cny_cost_price'    => '人民币成本价',
        'cny_market_price'  => '人民币市场价',
        'cny_retail_price'  => '人民币零售价',
        'hkd_cost_price'    => '港币成本价',
        'hkd_market_price'  => '港币市场价',
        'hkd_retail_price'  => '港币零售价',
        'manufacturer'      => '生产厂家',
        'service_phone'     => '售后电话',
        'status'            => '状态',
        'length'            => '长',
        'width'             => '宽',
        'height'            => '高',
        'gift_points'       => '赠送积分',
        'cost_points'       => '消费积分',
        'g_id'              => 'g_id',
    ],

    // 导入兼容别名：表头文字 => 字段（历史/旧模板/其它模块导出文件仍可导入）
    'import_aliases' => [
        // 旧单币种模板：成本价/市场价/零售价 归入 CNY
        '成本价'   => 'cost_price',
        '市场价'   => 'market_price',
        '零售价'   => 'retail_price',
        '售卖价'   => 'retail_price',
        // 旧导出文件别名
        '商品ID'   => 'g_id',
        '商品型号' => 'model',
        'SKU码'    => 'sku',
        '商品图片' => 'pic',
        // 目标模板（旧后台导出的商品模板）表头写法：
        // 表头匹配已做归一化（忽略大小写、去尾部冒号/星号、全角转半角），
        // 因此 "sku" 与 "SKU"、"关联SKU:" 与 "关联SKU" 都会命中同一字段。
        'sku'      => 'sku',
        '关联SKU'  => 'sku2',
        '供应商'   => 'manufacturer',
        '联系电话' => 'service_phone',
        // 其它常见同义写法
        '厂商'       => 'manufacturer',
        '供应商名称' => 'manufacturer',
        '生产商'     => 'manufacturer',
        '联系方式'   => 'service_phone',
        '售后电话'   => 'service_phone',
    ],

    /**
     * 导入行为开关
     * - allow_empty_cny_price：新增行（无商品ID或商品ID不存在）整行未提供任何 CNY 价格时，
     *   是否按 0.000 占位落库。开启后不带价格列的模板也能新增商品，返回体会用
     *   insert_zero_price / insert_zero_price_list 列出这些行，避免静默产生零价商品；
     *   关闭时新增行必须有完整 CNY 三价（原行为）。
     * - update_by_g_id_fields：有商品ID且商品存在时，按“单元格非空才更新”的字段白名单。
     *   bar_code 与各币种价格不在此列表内（条形码、价格走原有逻辑）。
     */
    'allow_empty_cny_price' => true,

    'update_by_g_id_fields' => [
        'bar_code', 'g_name', 'gc_id', 'gc_name', 'model', 'sku', 'sku2', 'pic',
        'manufacturer', 'service_phone', 'status', 'length', 'width', 'height',
    ],
];
