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
        "code_error" => "验证码错误",
    ],

    "export" => [
        "export_aul" => "导出用户事件",
    ],

    "VLogin" => [
        "account_require" => "账号不能为空",
        "password_require" => "密码不能为空",
        "code_require" => "验证码不能为空",
        "uniqid_require" => "验证码UUID不能为空",

        "logout_success" => "账号已退出登录",
        "logout_fail" => "账号退出登录失败",

        "account_not_exist" => "登录的账号不存在!",
        "account_pwd_incorrect" => "账号或密码错误，请重新输入",
        "pwd_incorrect" => "密码错误，请重新输入",
        "pass_the_verification" => "验证通过",
        "account_disabled" => "该账号已被禁用",
        "login_success" => "登录成功，正在跳转",

    ],

    "VActivity" => [
        "usedList_no_data" => "查无使用报表信息",
    ],

    "VActivityMachine" => [
        "machine_require" => "请选择适用设备",
        "machine_no_data" => "查无设备信息",
    ],

    "VActivityCoupon" => [
        "c_id_require" => "优惠券ID不能为空",
        "c_name_require" => "优惠券名称不能为空",
        "c_name_max" => "优惠券名称长度超过限制",
        "desc_max" => "优惠券说明长度超过限制",
        "start_date_require" => "开始日期不能为空",
        "end_date_require" => "结束日期不能为空",
        "c_type_require" => "优惠券类型不能为空",
        "designated_machine_require" => "适用机器不能为空",
        "designated_goods_require" => "适用商品不能为空",
    ],

    "VActivityCouponUsed" => [
        "cu_id_require" => "优惠券使用记录ID不能为空",
        "c_id_require" => "优惠券ID不能为空",
        "quantity_require" => "生成数量不能为空",
    ],

    "VActivityLottery" => [
        "al_no_data" => "查无活动信息",
        "al_id_require" => "活动ID不能为空",
        "lottery_name_require" => "活动名称不能为空",
        "start_time_require" => "开始时间不能为空",
        "price_require" => "单次抽奖金额不能为空",
        "desc_max" => "活动说明超过长度限制",

        "config_require" => "活动配置不能为空",
        "content_require" => "活动内容不能为空",
        "machineList_require" => "设备列表不能为空",
        "probability_no_100" => "中奖概率总和不是100%，请重新设置",

        "delContent_require" => "删除活动内容列表为必传项",
        "delConfig_require" => "删除活动配置列表为必传项",

        "content_name_require" => "活动内容名称不能为空",
        "designated_goods_require" => "指定商品不能为空",
        "retain_num_require" => "保留数量不能为空",
        "probability_require" => "中奖概率不能为空",
        "ag_require" => "商品列表不能为空",

        "active_num_require" => "抽奖次数不能为空",
        "active_type_require" => "抽奖类型不能为空",
    ],

    "VActivityFd" => [
        "goods_no_data" => "查无商品信息",

        "fd_id_require" => "活动ID不能为空",
        "fd_name_require" => "活动名称不能为空",
        "start_date_require" => "开始日期不能为空",
        "fd_type_require" => "活动类型不能为空",
        "condition_type_require" => "条件类型不能为空",
        "machineList_require" => "设备列表不能为空",
        "content_require" => "活动规则内容不能为空",

        "condition_value_require" => "条件数值不能为空",
        "active_value_require" => "活动值不能为空",
        "sort_require" => "规则排序值不能为空",
    ],

    "VActivityPick" => [
        "id_require" => "活动ID不能为空",
        "pick_no_data" => "查无取货码活动信息",
        "pick_name_require" => "活动名称不能为空",
        "start_time_require" => "开始时间不能为空",
        "pick_type_require" => "派送类型不能为空",
        "machineList_require" => "适用设备不能为空",
        "goodsList_require" => "适用商品不能为空",
        "status1" => "活动未开始",
        "status3" => "活动已结束",
        "status4" => "活动已下架",
        "machine_no_data" => "查无适用设备",

        "code_require" => "取货码不能为空",
        "m_id_require" => "请选择设备",
    ],

    "VActivityPickCode" => [
        "apc_id_require" => "取货码ID不能为空",
        "ap_id_require" => "活动ID不能为空",
        "quantity_require" => "生成数量不能为空",

        "pick_code_no_data" => "查无取货码信息",
        "status2" => "取货码已使用",
        "status3" => "取货码已过期",
        "status4" => "取货码已作废",
        "status5" => "取货码使用中",

        "create_order_fail" => "生成订单失败",
        "pick_type1" => "该取货码为系统随机出货类型的出货码，当前操作不能使用，请在终端上使用",

        "pick_code_require" => "取货码不能为空",
        "m_id_require" => "请选择设备",
        "g_id_require" => "请选择商品",
        "goods_no_data" => "查无商品信息",
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
    "VConfigApi" => [
        "id_require" => "请选择对外用户",
        "auth_name_require" => "用户名不能为空",
        "auth_password_require" => "用户名不能为空",
        "white_list_require" => "IP白名单不能为空",
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

        "m_ids_require" => "设备ID不能为空",
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

        "query_no_data" => "查无素材",
        "can_not_use" => "素材不可用",
    ],

    "VGoods" => [
        "goods_no_data" => "查无商品信息",
        "g_id_require" => "请选择商品",
        "g_name_require" => "商品名称不能为空",
        "g_name_max" => "商品名称长度超限制",
        "sku_require" => "SKU不能为空",
        "pic_max" => "图片路径长度超限制",
        "manufacturer_max" => "供应商名称长度超限制",
        "service_phone_max" => "联系电话长度超限制",
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

    "VGoodsChange" => [
        "create_time_require" => "时间段不能为空",
    ],

    "VGoodsCorner" => [
        "id_require" => "角标ID不能为空",
        "corner_name_require" => "角标名称不能为空",
        "corner_type_require" => "角标类型不能为空",
        "style_require" => "角标样式不能为空",
        "position_require" => "角标位置不能为空",
        "start_time_require" => "生效时间不能为空",
        "goodsList_require" => "适用商品不能为空",
        "machineList_require" => "适用设备不能为空",
    ] ,

    "VMachineGoods" => [

        "mg_id_require" => "请选择设备商品",
        "m_id_require" => "设备ID不能为空",
        "machine_id_require" => "设备编号不能为空",

        "mg_no_data" => "信息不能为空",

        "where_require" => "修改条件不能为空",
        "update_require" => "修改内容不能为空",

        "synchronization_fail" => "同步设备商品库失败",
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

    "VMachineGroupMg" => [
        "mg_id_require" => "设备分组ID不能为空",
        "m_id_require" => "设备ID不能为空",
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
        "machine_no_data" => "查无设备信息",
        "status_in" => "设备状态不在范围内",

        "light_require" => "灯光亮度不能为空",
        "volume_require" => "音量不能为空",
        "light_multiple" => "灯光亮度值必须为10的倍数",
        "volume_multiple" => "音量值必须为10的倍数",

        "machine_offline" => "设备离线",
    ],


    "VMachineChannel" => [
        "mc_id_require" => "货道ID不能为空",
        "m_id_require" => "设备ID不能为空",
        "machine_id_require" => "设备编号不能为空",
        "channel_code_require" => "货道编号不能为空",
        "synchronization_fail" => "同步设备货道失败",
    ],

    "VMachineErrorCode" => [
        "me_id_require" => "请选择错误码信息",
    ],
    "VMachineConfig" => [
        "mc_id_require" => "设备配置ID不能为空",
        "m_id_require" => "设备ID不能为空",
        "m_id_unique" => "设备配置已存在，请勿重复添加",
        "machine_id_require" => "设备编号不能为空",
        "mcList_require" => "批量配置列表参数不能为空",
    ],

    "VMachineOnOff" => [
        "moo_id_require" => "配置ID不能为空",
        "m_id_require" => "设备ID不能为空",
        "machine_id_require" => "设备编号不能为空",
        "machine_name_require" => "设备名称不能为空",
        "on_off_ckc_require" => "营业配置不能为空",
        "on_off_machine_require" => "定时开关机不能为空",
    ],

    "VMachineInfo" => [
        "mi_id_require" => "设备信息ID不能为空",
        "m_id_require" => "设备ID不能为空",
        "m_id_unique" => "设备信息已存在，请勿重复添加",
        "machine_id_require" => "设备编号不能为空",
        "title_require" => "标题不能为空",
        "content_require" => "内容不能为空",
        "lang_require" => "语言类型不能为空",
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
    "VMachineVersion" => [
        "mv_id_require" => "软件ID不能为空",
        "version_no_require" => "版本号不能为空",
        "version_no_max" => "版本号长度超限制",
        "path_require" => "文件路径不能为空",
        "path_max" => "文件路径长度超限制",
        "size_require" => "文件大小不能为空",
        "desc_max" => "版本说明超限制",
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

    "VAdvertisement" => [
        "adv_no_data" => "查无广告信息",
        "adv_id_require" => "推送广告ID不能为空",
        "adv_title_require" => "广告推送标题不能为空",
        "duration_time_require" => "播放时长不能为空",
        "total_times_require" => "总播放次数不能为空",
        "m_id_require" => "请选择设备",
        "start_date_require" => "日期列表不能为空",
        "end_date_require" => "日期列表不能为空",
        "time_list_require" => "时间段列表不能为空",
        "screen_require" => "请选择屏幕",
        "screen_full_require" => "请选择是否全屏",
        "resource_is_del" => "当前广告素材已被删除",

        "query_machine_no_data" => "查无设备信息",

        "upDown_where_empty" => "上下架条件不能为空",

        "remain_times_empty" => "广告播放次数已用完",
        "quantity_not_match" => "抽奖数量与出货数量不匹配",
    ],

    "VSaleOrders" => [
        "order_no_data" => "查无订单信息",
        "order_id_require" => "订单ID不能为空",
        "refund_require" => "退款数据不能为空",
    ],

    "VSaleOrdersUnclaimed" => [
        "su_id_require" => "事件ID不能为空",
        "status_require" => "操作值不能为空",
        "remark_max" => "备注信息超过限制",

        "su_no_data" => "查无未取数据",
    ],

    "VWxOfficial" => [
        "official_no_data" => "查无配置信息",
        "id_require" => "配置ID不能为空",
        "gh_id_require" => "公众号原始ID不能为空",
        "wx_name_require" => "公众号名称不能为空",
        "app_id_require" => "APPID不能为空",
        "secret_require" => "密钥不能为空",
        "token_require" => "TOKEN不能为空",
        "aes_key_require" => "加密密钥不能为空",
        "wx_txt_require" => "域名设置文件不能为空",
    ],

    "VWxTemplate" => [
        "wt_id_require" => "ID不能为空",
        "wx_id_require" => "公众号配置ID不能为空",
        "template_id_require" => "公众号消息模板ID不能为空",
        "body_require" => "消息模板主体信息不能为空",
    ],

    "VEmailConfig" => [
        "ec_id_require" => "配置ID不能为空",
        "host_require" => "邮件服务器不能为空",
        "username_require" => "发件方账号不能为空",
        "authCode_require" => "授权码不能为空",
        "sendEmail_require" => "发送方邮箱地址不能为空",
    ],

    "VEmailTemplate" => [
        "et_id_require"   => "消息模板ID不能为空",
        "subject_require" => "标题不能为空",
        "body_require"    => "正文信息不能为空",
        "attachment_max"    => "附件信息超过限制长度",
        "et_type_require" => "模板类型不能为空",
    ],
];