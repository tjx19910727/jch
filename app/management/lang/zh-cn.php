<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/6
 * Time: 10:04
 */
return [
    "captcha" => [
        "get_success" => "获取验证码成功",
    ],
    "VLogin" => [
        "account_require" => "账号不能为空",
        "password_require" => "密码不能为空",
        "code_require" => "验证码不能为空",
        "uniqid_require" => "验证码UUID不能为空",
    ],

    "VConfig" => [
        "config_id_require" => "请选择配置信息",
        "config_name_require" => "配置名称不能为空",
        "config_content_require" => "配置内容不能为空",
        "config_switch_require" => "配置开关不能为空",
        // size

        "s_id_require" => "请选择尺寸信息",
        "label_require" => "标签名不能为空",
        "length_require" => "长度不能为空",
        "length_number" => "长度必须为数字",
        "width_require" => "宽度不能为空",
        "width_number" => "宽度必须为数字",
        "type_require" => "尺寸类型不能为空",
        "type_number" => "尺寸类型必须为数字",

        // lang
        "l_id_require" => "请选择语言信息",
        "name_require" => "名称不能为空",
        "lang_require" => "语言编码不能为空",

        // performance
        "cp_id_require" => "记录ID不能为空",
        "name_max" => "名称长度超过限制",
        "field_require" => "字段名不能为空",
        "field_max" => "字段名长度超限制",
        "field_unique" => "字段名已存在，不允许重复",
        "lang_max" => "语言编码长度超限制",
    ],
    "VConfigScene" => [
        "id_require" => "请选择场景",
        "name_require" => "场景名称不能为空",
    ],

    // VAuth
    "VAuth" => [
        "name_require" => "节点名称不能为空",
        "manager_id_require" => "请选择管理员账号",
        "account_require" => "账号不能为空",
        "account_unique" => "账号已存在，请勿重复添加",
        "password_require" => "密码不能为空",
        "status_require" => "状态不能为空",
        "status_in" => "状态超出范围",
        "mr_id_require" => "管理员关联角色ID不能为空",
        "role_id_require" => "权限角色ID不能为空",
        "type_require" => "节点类型不能为空",
        "nodeList_require" => "节点列表不能为空",
        "rn_id_require" => "权限角色关联节点不能为空",
        "list_require" => "通知开关数据不能为空",
        "notice_type_require" => "开关类型不能为空",

        // auth_organization
        "ao_id_require" => '组织ID不能为空',
        "pid_require" => '上级组织ID不能为空',
        "organization_name_require" => '组织名称不能为空',

        "roleList_require" => "权限角色ID不能为空",
    ],

    "VResource" => [
        "res_id_require" => "素材ID不能为空",
        "title_require" => "素材名称不能为空",
        "title_max" => "素材名称不能超过100个字符",
        "file_path_require" => "素材路径不能为空",
        "file_path_max" => "素材路径不能超过255个字符",
        "type_require" => "请选择素材类型",
        "length_require" => "高度不能为空",
        "width_require" => "宽度不能为空",
        "size_require" => "素材大小不能为空",
    ],

    "VGoodsCategory" => [
        "gc_id_require" => "请选择商品分类",
        "gc_name_require" => "分类名称不能为空",
        "gc_name_max" => "分类名称长度超限制",
        "status_require" => "状态不能为空",
    ],

    "VGoodsCategoryLang" => [
        "gcl_id_require" => "请选择商品分类多语言信息",
        "gc_id_require" => "请选择商品分类",
        "gc_name_require" => "分类名称不能为空",
        "gc_name_max" => "分类名称长度超限制",
    ],

    "VGoods" => [
        "g_id_require" => "请选择商品",
        "g_name_require" => "商品名称不能为空",
        "g_name_max" => "商品名称长度超限制",
        "pic_max" => "图片路径长度超限制",
        "manufacturer_max" => "供应商名称长度超限制",
        "service_phone_max" => "联系电话长度超限制",
    ],

    "VGoodsLang" => [
        "gl_id_require" => "请选择商品",
        "g_id_require" => "商品ID不能为空",
        "g_name_require" => "商品名称不能为空",
        "g_name_max" => "商品名称长度超限制",
        "manufacturer_max" => "供应商名称长度超限制",
    ],

    "VMachineGroup" => [
        "mg_id_require" => "请选择设备分组",
        "mg_name_require" => "分组名称不能为空",
        "mg_name_max" => "分组名称长度超限制",
        "desc_require" => "分组描述不能为空",
        "desc_max" => "分组描述长度超限制",
    ],

    "VMachineGroupLang" => [
        "mgl_id_require" => "请选择设备分组语言信息",
        "mg_id_require" => "设备分组ID不能为空",
        "mg_name_require" => "分组名称不能为空",
        "mg_name_max" => "分组名称长度超限制",
        "desc_require" => "分组描述不能为空",
        "desc_max" => "分组描述长度超限制",
        "lang_require" => "语言编码不能为空",
        "lang_max" => "语言编码长度超限制",
    ],

    "VMachine" => [
        "m_id_require" => "请选择设备",
        "machine_id_require" => "设备编号不能为空",
    ],

    "VMachineGoods" => [
        "mg_id_require" => "请选择设备商品",
        "m_id_require" => "设备ID不能为空",
        "machine_id_require" => "设备编号不能为空",
        "g_id_require" => "请选择商品",
        "g_name_require" => "商品名称不能为空",
    ],

    "VMachineChannel" => [
        "mc_id_require" => "货道ID不能为空",
        "m_id_require" => "设备ID不能为空",
        "machine_id_require" => "设备编号不能为空",
        "channel_code_require" => "货道编号不能为空",
    ],

    "VMachineConfig" => [
        "mc_id_require" => "设备配置ID不能为空",
        "m_id_require" => "设备ID不能为空",
        "m_id_unique" => "设备配置已存在，请勿重复添加",
        "machine_id_require" => "设备编号不能为空",
    ],

    "VMachineInfo" => [
        "mi_id_require" => "设备信息ID不能为空",
        "m_id_require" => "设备ID不能为空",
        "m_id_unique" => "设备信息已存在，请勿重复添加",
        "machine_id_require" => "设备编号不能为空",
    ],

    "VMachineHelp" => [
        "mi_id_require" => "设备帮助信息ID不能为空",
        "m_id_require" => "设备ID不能为空",
        "machine_id_require" => "设备编号不能为空",
    ],

    "VMachineView" => [
        "mv_id_require" => "请选择设备模板",
        "template_id_require" => "模板ID不能为空",
        "view_id_require" => "视图ID不能为空",
        "m_id_require" => "设备ID不能为空",
        "machine_id_query_no_data" => "查无设备信息",
        "name_require" => "设备模板名称不能为空",
        "publish_time_require" => "生效时间不能为空",
    ],

    "VTemplate" => [
        "id_require" => "模板ID不能为空",
        "name_require" => "模板名称不能为空",
        "resolution_require" => "模板分辨率不能为空",
    ],

    "VTemplateLayout" => [
        "id_require" => "请选择区域",
        "name_require" => "布局区域名称不能为空",
        "template_id_require" => "模板ID不能为空",
        "height_require" => "高度不能为空",
        "width_require" => "宽度不能为空",
        "left_require" => "左偏差值不能为空",
        "top_require" => "顶部偏差值不能为空",
    ],

    "VTemplatePlugins" => [
        "id_require" => "请选择插件",
        "plugin_name_require" => "插件名称不能为空",
        "display_name_require" => "插件显示名称不能为空",
        "type_require" => "插件类型不能为空",
    ],

    "VTemplateView" => [
        "id_require" => "请选择模板视图",
        "name_require" => "视图名称不能为空",
        "template_id_require" => "模板ID不能为空",
        "height_require" => "高度不能为空",
        "width_require" => "宽度不能为空",
        "plugin_data_require" => "插件数据不能为空",

        "layout_id_require" => "布局ID不能为空",
        "left_require" => "左偏差值不能为空",
        "top_require" => "顶偏差值不能为空",
    ],
];