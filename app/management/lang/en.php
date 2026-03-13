<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/6
 * Time: 10:04
 */
return [
    "captcha" => [
        "get_success" => "Captcha obtained successfully",
        "code_error" => "Incorrect captcha",
    ],

    "menu" => [
        "goods_management" => "Product Management",
    ],

    "export" => [
        "export_aul" => "Export User Events",

        "export_log_create_fail" => "Failed to generate export log record",
        "exporting" => "Exporting data",
        "g_id" => "Product ID",
        "g_name" => "Product Name",
        "g_type" => "Product Type",
        "g_type1" => "Regular Product",
        "g_type2" => "Hotel Product",
        "g_type3" => "Ticket Product",
        "g_type_unDefine" => "Undefined Product Type",
        "gc_name" => "Product Category",
        "model" => "Product Model",
        "bar_code" => "Bar Code",
        "sku" => "SKU Code",
        "pic" => "Goods Picture",
        "cost_price" => "Cost Price",
        "market_price" => "Market Price",
        "totalQuantity" => "Sales Volume",
        "retail_price" => "Retail Price",
        "manufacturer" => "Provider",
        "service_phone" => "Provider Phone",
        "status" => "Status",
        "status1" => "Able",
        "status2" => "Disable",
        "length" => "Length",
        "width" => "Width",
        "height" => "Height",
        "goods10List" => "Popular Products Ranking (Last 7 Days) -",

        "goods_list" => "Product List",

        "goodsRankingFileName" => "Homepage - Popular Products Ranking (Last 7 Days)",

    ],

    "getSelfRoleNode" => [
        "no_data" => "Current account is not authorized, cannot log in to the system",
    ],

    "VLogin" => [
        "account_require" => "Account cannot be empty",
        "password_require" => "Password cannot be empty",
        "code_require" => "Captcha cannot be empty",
        "uniqid_require" => "Captcha UUID cannot be empty",

        "logout_success" => "Account logged out successfully",
        "logout_fail" => "Failed to log out account",

        "account_not_exist" => "The account does not exist!",
        "account_pwd_incorrect" => "Incorrect account or password, please try again",
        "pwd_incorrect" => "Incorrect password, please try again",
        "pass_the_verification" => "Verification passed",
        "account_disabled" => "This account has been disabled",
        "login_success" => "Login successful, redirecting",

    ],
    "VWxLogin" => [
        "official_require" => "No official account information",
        "wx_no_data" => "No official account information found",
        "wx_status2" => "Official account has been disabled",
    ],

    "VActionVideo" => [
        "id_require" => "Operation video ID cannot be empty",
        "video_name_require" => "Operation video name cannot be empty",
        "path_require" => "Please upload the video",
    ],

    "VActivity" => [
        "usedList_no_data" => "No usage report information found",
    ],

    "VActivityMachine" => [
        "machine_require" => "Please select applicable device",
        "machine_no_data" => "No device information found",
    ],

    "VActivityCoupon" => [
        "c_id_require" => "Coupon ID cannot be empty",
        "c_name_require" => "Coupon name cannot be empty",
        "c_name_max" => "Coupon name exceeds length limit",
        "desc_max" => "Coupon description exceeds length limit",
        "start_date_require" => "Start date cannot be empty",
        "end_date_require" => "End date cannot be empty",
        "c_type_require" => "Coupon type cannot be empty",
        "designated_machine_require" => "Applicable machine cannot be empty",
        "designated_goods_require" => "Applicable product cannot be empty",
    ],

    "VActivityCouponUsed" => [
        "cu_id_require" => "Coupon usage record ID cannot be empty",
        "c_id_require" => "Coupon ID cannot be empty",
        "quantity_require" => "Generation quantity cannot be empty",
    ],

    "VActivityLottery" => [
        "al_no_data" => "No activity information found",
        "al_id_require" => "Activity ID cannot be empty",
        "lottery_name_require" => "Activity name cannot be empty",
        "start_time_require" => "Start time cannot be empty",
        "price_require" => "Single lottery amount cannot be empty",
        "desc_max" => "Activity description exceeds length limit",

        "config_require" => "Activity configuration cannot be empty",
        "content_require" => "Activity content cannot be empty",
        "machineList_require" => "Device list cannot be empty",
        "probability_no_100" => "Total winning probability is not 100%, please reset",

        "delContent_require" => "Deleted activity content list is required",
        "delConfig_require" => "Deleted activity configuration list is required",

        "content_name_require" => "Activity content name cannot be empty",
        "designated_goods_require" => "Designated product cannot be empty",
        "retain_num_require" => "Retained quantity cannot be empty",
        "probability_require" => "Winning probability cannot be empty",
        "ag_require" => "Product list cannot be empty",

        "active_num_require" => "Lottery times cannot be empty",
        "active_type_require" => "Lottery type cannot be empty",
    ],

    "VActivityFd" => [
        "goods_no_data" => "No product information found",

        "fd_id_require" => "Activity ID cannot be empty",
        "fd_name_require" => "Activity name cannot be empty",
        "start_date_require" => "Start date cannot be empty",
        "fd_type_require" => "Activity type cannot be empty",
        "condition_type_require" => "Condition type cannot be empty",
        "machineList_require" => "Device list cannot be empty",
        "content_require" => "Activity rule content cannot be empty",

        "g_id_require" => "Product ID cannot be empty",

        "condition_value_require" => "Condition value cannot be empty",
        "active_value_require" => "Activity value cannot be empty",
        "sort_require" => "Rule sorting value cannot be empty",
    ],

    "VActivityPick" => [
        "id_require" => "Activity ID cannot be empty",
        "pick_no_data" => "No pickup code activity information found",
        "pick_name_require" => "Activity name cannot be empty",
        "start_time_require" => "Start time cannot be empty",
        "pick_type_require" => "Delivery type cannot be empty",
        "machineList_require" => "Applicable device cannot be empty",
        "goodsList_require" => "Applicable product cannot be empty",
        "status1" => "Activity has not started",
        "status3" => "Activity has ended",
        "status4" => "Activity has been taken offline",
        "machine_no_data" => "No applicable device found",

        "code_require" => "Pickup code cannot be empty",
        "m_id_require" => "Please select device",
    ],

    "VActivityPickCode" => [
        "apc_id_require" => "Pickup code ID cannot be empty",
        "ap_id_require" => "Activity ID cannot be empty",
        "quantity_require" => "Generation quantity cannot be empty",

        "pick_code_no_data" => "No pickup code information found",
        "status2" => "Pickup code has been used",
        "status3" => "Pickup code has expired",
        "status4" => "Pickup code has been voided",
        "status5" => "Pickup code is in use",

        "create_order_fail" => "Failed to generate order",
        "pick_type1" => "This pickup code is for system random delivery type and cannot be used in current operation, please use it on the terminal",

        "pick_code_require" => "Pickup code cannot be empty",
        "m_id_require" => "Please select device",
        "g_id_require" => "Please select product",
        "goods_no_data" => "No product information found",
    ],

    "VConfig" => [
        "config_id_require" => "Please select configuration information",
        "config_name_require" => "Configuration name cannot be empty",
        "config_content_require" => "Configuration content cannot be empty",
        "config_switch_require" => "Configuration switch cannot be empty",
        // size

        "s_id_require" => "Please select size information",
        "label_require" => "Label name cannot be empty",
        "length_require" => "Length cannot be empty",
        "length_number" => "Length must be a number",
        "width_require" => "Width cannot be empty",
        "width_number" => "Width must be a number",
        "type_require" => "Size type cannot be empty",
        "type_number" => "Size type must be a number",

        // lang
        "l_id_require" => "Please select language information",
        "name_require" => "Name cannot be empty",
        "lang_require" => "Language code cannot be empty",

        // performance
        "cp_id_require" => "Record ID cannot be empty",
        "name_max" => "Name exceeds length limit",
        "field_require" => "Field name cannot be empty",
        "field_max" => "Field name exceeds length limit",
        "field_unique" => "Field name already exists, duplicates not allowed",
        "lang_max" => "Language code exceeds length limit",
    ],
    "VConfigApi" => [
        "id_require" => "Please select external user",
        "auth_name_require" => "Username cannot be empty",
        "auth_password_require" => "Username cannot be empty",
        "white_list_require" => "IP whitelist cannot be empty",
    ],

    "VConfigScene" => [
        "id_require" => "Please select scene",
        "name_require" => "Scene name cannot be empty",
    ],

    // VAuth
    "VAuth" => [
        "name_require" => "Node name cannot be empty",
        "manager_id_require" => "Please select administrator account",
        "account_require" => "Account cannot be empty",
        "account_unique" => "Account already exists, do not add duplicate",
        "password_require" => "Password cannot be empty",
        "old_pwd_require" => "Please enter password",
        "status_require" => "Status cannot be empty",
        "status_in" => "Status out of range",
        "mr_id_require" => "Manager role association ID cannot be empty",
        "role_id_require" => "Permission role ID cannot be empty",
        "type_require" => "Node type cannot be empty",
        "nodeList_require" => "Node list cannot be empty",
        "rn_id_require" => "Permission role association node cannot be empty",
        "list_require" => "Notification switch data cannot be empty",
        "notice_type_require" => "Switch type cannot be empty",

        // auth_organization
        "ao_id_require" => 'Organization ID cannot be empty',
        "pid_require" => 'Parent organization ID cannot be empty',
        "organization_name_require" => 'Organization name cannot be empty',

        "roleList_require" => "Permission role ID cannot be empty",

        "m_ids_require" => "Device ID cannot be empty",
    ],

    "VResource" => [
        "res_id_require" => "Material ID cannot be empty",
        "title_require" => "Material name cannot be empty",
        "title_max" => "Material name cannot exceed 100 characters",
        "file_path_require" => "Material path cannot be empty",
        "file_path_max" => "Material path cannot exceed 255 characters",
        "type_require" => "Please select material type",
        "length_require" => "Height cannot be empty",
        "width_require" => "Width cannot be empty",
        "size_require" => "Material size cannot be empty",

        "query_no_data" => "No material found",
        "can_not_use" => "Material unavailable",
    ],

    "VGoods" => [
        "goods_no_data" => "No product information found",
        "g_id_require" => "Please select product",
        "g_name_require" => "Product name cannot be empty",
        "g_name_max" => "Product name exceeds length limit",
        "sku_require" => "SKU cannot be empty",
        "sku_unique" => "SKU already exists, do not add duplicate",
        "pic_max" => "Image path exceeds length limit",
        "banner_max" => "Carousel image exceeds length limit",
        "manufacturer_max" => "Supplier name exceeds length limit",
        "service_phone_max" => "Contact number exceeds length limit",
        "release_time_require" => "Release time cannot be empty",
        "length_require" => "Product length cannot be empty",
        "width_require" => "Product width cannot be empty",
        "height_require" => "Product height cannot be empty",
        "g_is_up" => "Product is already on shelf",
    ],

    "VGoodsLang" => [
        "gl_id_require" => "Please select product",
        "g_id_require" => "Product ID cannot be empty",
        "g_name_require" => "Product name cannot be empty",
        "g_name_max" => "Product name exceeds length limit",
        "manufacturer_max" => "Supplier name exceeds length limit",
        "lang_require" => "Language code cannot be empty",
        "is_exist" => "Current language data already exists, do not add duplicate",
    ],

    "VGoodsCategory" => [
        "gc_id_require" => "Please select product category",
        "gc_name_require" => "Category name cannot be empty",
        "gc_name_max" => "Category name exceeds length limit",
        "status_require" => "Status cannot be empty",
    ],

    "VGoodsCategoryLang" => [
        "gcl_id_require" => "Please select product category multilingual information",
        "gc_id_require" => "Please select product category",
        "gc_name_require" => "Category name cannot be empty",
        "gc_name_max" => "Category name exceeds length limit",
    ],

    "VGoodsChange" => [
        "create_time_require" => "Time period cannot be empty",
    ],

    "VGoodsCorner" => [
        "id_require" => "Corner mark ID cannot be empty",
        "corner_name_require" => "Corner mark name cannot be empty",
        "corner_type_require" => "Corner mark type cannot be empty",
        "style_require" => "Corner mark style cannot be empty",
        "position_require" => "Corner mark position cannot be empty",
        "start_time_require" => "Effective time cannot be empty",
        "goodsList_require" => "Applicable product cannot be empty",
        "machineList_require" => "Applicable device cannot be empty",
    ] ,

    "VGoodsMultiple" => [
        "gm_id_require" => "Combination product ID cannot be empty",
        "gm_name_require" => "Product name cannot be empty",
        "start_time_require" => "Please set start date",
        "mList_require" => "Please select applicable device",
        "m_id_require" => "Device ID cannot be empty",
        "machine_id_require" => "Device number cannot be empty",
        "gList_require" => "Please select combination product",
        "g_id_require" => "Product ID cannot be empty",
        "selling_price_require" => "Selling price cannot be empty",
        "rise_fall_ratio_require" => "Rise/fall ratio cannot be empty",
    ],

    "VHotel" => [
        "cityId_require" => "City ID cannot be empty",
        "page_require" => "Query page number cannot be empty",
        "pageNum_require" => "Page size cannot be empty",
    ],

    "VMachineGoods" => [

        "mg_id_require" => "Please select device product",
        "m_id_require" => "Device ID cannot be empty",
        "machine_id_require" => "Device number cannot be empty",

        "mg_no_data" => "Information cannot be empty",

        "where_require" => "Modification condition cannot be empty",
        "update_require" => "Modification content cannot be empty",

        "synchronization_fail" => "Failed to synchronize device product library",
    ],

    "VMachineGroup" => [
        "mg_id_require" => "Please select device group",
        "mg_name_require" => "Group name cannot be empty",
        "mg_name_max" => "Group name exceeds length limit",
        "desc_require" => "Group description cannot be empty",
        "desc_max" => "Group description exceeds length limit",
    ],

    "VMachineGroupMg" => [
        "mg_id_require" => "Device group ID cannot be empty",
        "m_id_require" => "Device ID cannot be empty",

        "machine_list_empty" => "No devices bound to current group",
    ],

    "VMachineGroupLang" => [
        "mgl_id_require" => "Please select device group language information",
        "mg_id_require" => "Device group ID cannot be empty",
        "mg_name_require" => "Group name cannot be empty",
        "mg_name_max" => "Group name exceeds length limit",
        "desc_require" => "Group description cannot be empty",
        "desc_max" => "Group description exceeds length limit",
        "lang_require" => "Language code cannot be empty",
        "lang_max" => "Language code exceeds length limit",
    ],

    "VMachine" => [
        "m_id_require" => "Please select device",
        "machine_id_require" => "Device number cannot be empty",
        "machine_id_alphaDash" => "Device number can only contain letters and numbers, '-','_' are also allowed",
        "machine_id_exists" => "The equipment number already exists. Please do not add it repeatedly.",
        "machine_no_data" => "No device information found",
        "status_in" => "Device status out of range",

        "light_require" => "Light brightness cannot be empty",
        "volume_require" => "Volume cannot be empty",
        "light_multiple" => "Light brightness value must be a multiple of 10",
        "volume_multiple" => "Volume value must be a multiple of 10",

        "machine_offline" => "Device offline",
        "ckc_status_require" => "Business status cannot be empty",
        "x_y_axis_require" => "x，y axis cannot be all empty",
    ],

    "VMachineLang" => [
        "ml_id_require" => "Please select device multilingual data",
        "m_id_require" => "Please select device",
        "machine_id_require" => "Device number cannot be empty",
        "machine_no_data" => "No device multilingual information found",
        "is_exist" => "Current language data already exists, do not add again",
    ],


    "VMachineChannel" => [
        "mc_id_require" => "Channel ID cannot be empty",
        "m_id_require" => "Device ID cannot be empty",
        "machine_id_require" => "Device number cannot be empty",
        "channel_code_require" => "Channel number cannot be empty",
        "synchronization_fail" => "Failed to synchronize device channel",
        "update_price_error" => "Locked shelf price error",
    ],

    "VMachineErrorCode" => [
        "me_id_require" => "Please select error code information",
        "error_code_require" => "Error code cannot be empty",
    ],
    "VMachineConfig" => [
        "mc_id_require" => "Device configuration ID cannot be empty",
        "m_id_require" => "Device ID cannot be empty",
        "m_id_unique" => "Device configuration already exists, do not add duplicate",
        "machine_id_require" => "Device number cannot be empty",
        "mcList_require" => "Batch configuration list parameter cannot be empty",
    ],
    "VMachineConfigLang" => [
        "mcl_id_require" => "Device configuration multilingual ID cannot be empty",
        "lang_require" => "Language code cannot be empty",
        "mc_id_require" => "Device configuration ID cannot be empty",
        "m_id_require" => "Device ID cannot be empty",
        "machine_id_require" => "Device number cannot be empty",
        "mcList_require" => "Batch configuration list parameter cannot be empty",
        "is_exist" => "Current language configuration information already exists, do not add duplicate",
    ],

    "VMachineOnOff" => [
        "moo_id_require" => "Configuration ID cannot be empty",
        "m_id_require" => "Device ID cannot be empty",
        "machine_id_require" => "Device number cannot be empty",
        "machine_name_require" => "Device name cannot be empty",
        "on_off_ckc_require" => "Business configuration cannot be empty",
        "on_off_machine_require" => "Scheduled power on/off cannot be empty",
        "is_exists" => "The business configuration of this device already exists. Please do not add it repeatedly.",
    ],

    "VMachineInfo" => [
        "mi_id_require" => "Device information ID cannot be empty",
        "m_id_require" => "Device ID cannot be empty",
        "m_id_unique" => "Device information already exists, do not add duplicate",
        "machine_id_require" => "Device number cannot be empty",
        "title_require" => "Title cannot be empty",
        "content_require" => "Content cannot be empty",
        "lang_require" => "Language type cannot be empty",
        "get_computer_overtime" => "Timeout getting central computer data",
    ],

    "VMachineHelp" => [
        "mi_id_require" => "Device help information ID cannot be empty",
        "m_id_require" => "Device ID cannot be empty",
        "machine_id_require" => "Device number cannot be empty",
    ],

    "VMachineView" => [
        "mv_id_require" => "Please select device template",
        "template_id_require" => "Template ID cannot be empty",
        "view_id_require" => "View ID cannot be empty",
        "m_id_require" => "Device ID cannot be empty",
        "machine_id_query_no_data" => "No device information found",
        "name_require" => "Device template name cannot be empty",
        "publish_time_require" => "Effective time cannot be empty",
    ],
    "VMachineVersion" => [
        "mv_id_require" => "Software ID cannot be empty",
        "version_no_require" => "Version number cannot be empty",
        "version_no_max" => "Version number exceeds length limit",
        "path_require" => "File path cannot be empty",
        "path_max" => "File path exceeds length limit",
        "size_require" => "File size cannot be empty",
        "desc_max" => "Version description exceeds limit",
    ],

    "VTripMultiple" => [
        "delHotel_notEmpty" => "Please select hotels to delete",
        "delGoods_notEmpty" => "Please select products to delete",
        "delMachine_notEmpty" => "Please select devices to delete",

        "tm_id_require" => "Trip combination product ID cannot be empty",
        "tm_name_require" => "Package name cannot be empty",
        "status_require" => "Status cannot be empty",
        "designated_hotel_require" => "Designated hotel cannot be empty",
        "designated_goods_require" => "Designated product cannot be empty",
        "designated_machine_require" => "Designated device cannot be empty",
        "machineList_require" => "Please select device",

        "tmm_id_require" => "Designated device ID cannot be empty",
        "m_id_require" => "Device ID cannot be empty",
        "machine_id_require" => "Device number cannot be empty",
        "machine_name_require" => "Device name cannot be empty",

        "tmh_id_require" => "Trip combination product hotel ID cannot be empty",
        "tc_id_require" => "Please select trip city",
        "cityId_require" => "Trip city ID cannot be empty",
        "cityName_require" => "Trip city name cannot be empty",
        "hotelId_require" => "Trip hotel ID cannot be empty",
        "hotelName_require" => "Trip hotel name cannot be empty",


        "tmg_id_require" => "Package product cannot be empty",
        "g_id_require" => "Please select designated product",
        "is_required_required" => "Please confirm if it's mandatory",
        "buy_lower_required" => "Purchase lower limit cannot be empty",
        "buy_lower_min" => "Purchase lower limit cannot be less than 1",
        "buy_upper_required" => "Purchase upper limit cannot be empty",
        "sale_amount_require" => "Please set selling price",

        "hotelId_unique" => "This hotel information already exists",
        "g_id_unique" => "This product information already exists",
        "m_id_unique" => "This device information already exists",
    ],

    "VTemplate" => [
        "id_require" => "Template ID cannot be empty",
        "name_require" => "Template name cannot be empty",
        "resolution_require" => "Template resolution cannot be empty",
    ],

    "VTemplateLayout" => [
        "id_require" => "Please select area",
        "name_require" => "Layout area name cannot be empty",
        "template_id_require" => "Template ID cannot be empty",
        "height_require" => "Height cannot be empty",
        "width_require" => "Width cannot be empty",
        "left_require" => "Left offset cannot be empty",
        "top_require" => "Top offset cannot be empty",
    ],

    "VTemplatePlugins" => [
        "id_require" => "Please select plugin",
        "plugin_name_require" => "Plugin name cannot be empty",
        "display_name_require" => "Plugin display name cannot be empty",
        "type_require" => "Plugin type cannot be empty",
    ],

    "VTemplateView" => [
        "id_require" => "Please select template view",
        "name_require" => "View name cannot be empty",
        "template_id_require" => "Template ID cannot be empty",
        "height_require" => "Height cannot be empty",
        "width_require" => "Width cannot be empty",
        "plugin_data_require" => "Plugin data cannot be empty",

        "layout_id_require" => "Layout ID cannot be empty",
        "left_require" => "Left offset cannot be empty",
        "top_require" => "Top offset cannot be empty",
    ],

    "VAdvertisement" => [
        "adv_no_data" => "No advertisement information found",
        "adv_id_require" => "Push advertisement ID cannot be empty",
        "adv_title_require" => "Advertisement push title cannot be empty",
        "duration_time_require" => "Play duration cannot be empty",
        "total_times_require" => "Total play times cannot be empty",
        "m_id_require" => "Please select device",
        "start_date_require" => "Date list cannot be empty",
        "end_date_require" => "Date list cannot be empty",
        "time_list_require" => "Time period list cannot be empty",
        "screen_require" => "Please select screen",
        "screen_full_require" => "Please select if full screen",
        "push_type_require" => "Push type cannot be empty",
        "push_type_in" => "Push type out of range",


        "resource_is_del" => "Current advertisement material has been deleted",

        "query_machine_no_data" => "No device information found",

        "upDown_where_empty" => "Online/offline condition cannot be empty",

        "remain_times_empty" => "Advertisement play times used up",
        "quantity_not_match" => "Lottery quantity does not match shipment quantity",
    ],

    "VSaleOrders" => [
        "order_no_data" => "No order information found",
        "order_id_require" => "Order ID cannot be empty",
        "refund_require" => "Refund data cannot be empty",

        "sod_id_require" => "Order detail ID cannot be empty",
        "checkOff_status_error" => "Verification status error",

        "payee_config_no_data" => "No payee configuration information found",
        "payee_config_no_json" => "Payee configuration information is not in JSON format",

        "free_can_not_refund" => "Free payment method cannot be refunded",
    ],

    "VSaleOrdersRefund" => [
        "order_no_data" => "No order data found",
        "refunding" => "There is a record of a submitted refund application for the current order. Please wait for the refund result before submitting again.",
    ],

    "VSaleOrdersUnclaimed" => [
        "su_id_require" => "Event ID cannot be empty",
        "status_require" => "Operation value cannot be empty",
        "remark_max" => "Remark information exceeds limit",

        "su_no_data" => "No unclaimed data found",
    ],

    "VWxOfficial" => [
        "official_no_data" => "No configuration information found",
        "id_require" => "Configuration ID cannot be empty",
        "gh_id_require" => "Official account original ID cannot be empty",
        "wx_name_require" => "Official account name cannot be empty",
        "app_id_require" => "APPID cannot be empty",
        "secret_require" => "Secret key cannot be empty",
        "token_require" => "TOKEN cannot be empty",
        "aes_key_require" => "Encryption key cannot be empty",
        "wx_txt_require" => "Domain setting file cannot be empty",
        "unbind_success" => "Unbind successful",
    ],

    "VWxTemplate" => [
        "wt_id_require" => "ID cannot be empty",
        "wx_id_require" => "Official account configuration ID cannot be empty",
        "template_id_require" => "Official account message template ID cannot be empty",
        "body_require" => "Message template body information cannot be empty",
    ],

    "VEmailConfig" => [
        "ec_id_require" => "Configuration ID cannot be empty",
        "host_require" => "Mail server cannot be empty",
        "username_require" => "Sender account cannot be empty",
        "authCode_require" => "Authorization code cannot be empty",
        "sendEmail_require" => "Sender email address cannot be empty",
    ],

    "VEmailTemplate" => [
        "et_id_require"   => "Message template ID cannot be empty",
        "subject_require" => "Title cannot be empty",
        "body_require"    => "Body information cannot be empty",
        "attachment_max"    => "Attachment information exceeds length limit",  
        "template_type_require" => "Template type cannot be empty",
    ],

    "VMicroMall" => [
        "mm_id_require" => "Micro mall ID cannot be empty",
        "mall_name_require" => "Micro mall name cannot be empty",
    ],

    "VSuggest" => [
        "s_id_require" => "Please select suggestion information",
        "content_require" => "Suggestion content cannot be empty",
        "content_length" => "Content exceeds length limit",
        "pic_length" => "Image attachment exceeds limit",
        "email_require" => "Email cannot be empty",
    ],

    "VMall" => [
        "mall_id_require" => "Mall ID cannot be empty",
        "mall_name_require" => "Mall name cannot be empty",
        "type_in" => "Invalid mall type",
        "status_in" => "Invalid mall status",
    ],

    "VMallMachine" => [
        "mall_id_require" => "Mall ID cannot be empty",
        "machine_id_require" => "Machine ID cannot be empty",
        "status_in" => "Invalid status",
    ],

    "VCard" => [
        "card_no_require" => "Card NO cannot be empty",
        "points_changed_require" => "points_changed cannot be empty",
        "change_type_in" => "Invalid change_type",
    ],
    "VMachineLevelDesc" => [
        "name_require" => "Level Name cannot be empty",
        "pic_require" => "Image cannot be empty",
        "machine_level_require" =>"Level ID cannot be empty",
        "machine_level_gt" => "Level ID must be a positive integer",
    ],
];