<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 9:56
 */

namespace app\machine\validate;


class VReceive extends VCommon
{
    protected $rule = [

        "machine_id" => "require",
        "msg_id" => "require|unique:machine_mq_record",
        "timestamp" => "require|checkTimestamp",
        "sign" => "require",

        "pay_type" => "require",
        "carList" => "require",

        "mcList" => "require",


        "operator" => "require",
        "repList" => "require",

        "mc_id" => "require",
        "g_id" => "require",
        "quantity" => "require",
        "standby_quantity" => "require",

        "adv_id" => "require",
        "play_time" => "require",

        "account" => "require",
        "password" => "require",

        "manager_id" => "require",
        "card_no" => "require",


        "pay_method" => "require",
        "total_price" => "require",
        "total_quantity" => "require",
        "alc_id" => "require",

        "order_id" => "require",
        "fd_id" => "require",

        "pick_code" => "require",

        "details" => "require",

        "mvp_id" => "require",
        "download_progress" => "require",

        "gm_id" => "require",
        "gmg_id" => "require",
        "trade_no" => "require",
        "mobile" => "require|max:32",
        "input_code" => "require|regex:/^\d{4}$/",
        "pic_out_goods_box" => "require|max:1000",
        "video_out_goods_box" => "require|max:1000",
        "video_refund_goods" => "require|max:1000",
        "item_ids" => "require",
        "check_list" => "require|array",
    "maintainer_id" => "require",
        "status" => "integer",
        "field" => "require",
        "date" => "require",
        "machine_usage" => "require",
        "rsrp" => "require",
        "sinr" => "require",
        "file_content" => "require",
        "per_row" => "integer",
        "last_login_info_id" => "integer",
        "staff_code" => "require|regex:/^[1-9][0-9]{5}$/",
    ];

    protected $message = [

        "machine_id.require" => "VReceive.machine_id_require",
        "msg_id.require" => "VReceive.msg_id_require",
        "msg_id.unique" => "VReceive.msg_id_unique",
        "timestamp.require" => "VReceive.timestamp_require",
        "sign.require" => "VReceive.sign_require",

        "pay_type.require" => "VReceive.pay_type_require",
        "pay_method.require" => "VReceive.pay_method_require",
        "total_price.require" => "VReceive.total_price_require",
        "total_quantity.require" => "VReceive.total_quantity_require",
        "alc_id.require" => "VReceive.alc_id_require",

        "carList.require" => "VReceive.carList_require",

        "hotelList.require" => "VReceive.hotelList_require",

        "mcList.require" => "VReceive.mcList_require",

        "operator.require" => "VReceive.operator_require",
        "repList.require" => "VReceive.mcList_require",

        "mc_id.require" => "VReceive.mc_id_require",
        "g_id.require" => "VReceive.g_id_require",
        "quantity.require" => "VReceive.quantity_require",
        "standby_quantity.require" => "VReceive.standby_quantity_require",

        "adv_id.require" => "VAdvertisement.adv_id_require",
        "play_time.require" => "VAdvertisement.play_time_require",

        "account.require" => "VLogin.account_require",
        "password.require" => "VLogin.password_require",

        "manager_id.require" => "VReceive.manager_id_require",
        "card_no.require" => "VReceive.card_no_require",

        "order_id.require" => "VReceive.order_id_require",
        "fd_id.require" => "VReceive.fd_id_require",

        "pick_code.require" => "VReceive.pick_code_require",
        "coupon_code.require" => "VReceive.coupon_code_require",


        "details.require" => "VReceive.details_require",

        "mvp_id.require" => "VReceive.mvp_id_require",
        "download_progress.require" => "VReceive.download_progress_require",

        "gm_id.require" => "VSubGoodsMultipleOrder.gm_id_require",
        "gmg_id.require" => "VSubGoodsMultipleOrder.gmg_id_require",

        "trade_no.require" => "VReceive.trade_no_require",
        "mobile.require" => "VReceive.mobile_require",
        "mobile.max" => "手机号长度不能超过32个字符",
        "input_code.require" => "四位数字编码不能为空",
        "input_code.regex" => "编码必须是四位数字",
        "pic_out_goods_box.require" => "出料箱图片不能为空",
        "pic_out_goods_box.max" => "出料箱图片地址长度不能超过1000个字符",
        "video_out_goods_box.require" => "出料箱视频不能为空",
        "video_out_goods_box.max" => "出料箱视频地址长度不能超过1000个字符",
        "video_refund_goods.require" => "全局退货视频不能为空",
        "video_refund_goods.max" => "全局退货视频地址长度不能超过1000个字符",
        "item_ids.require" => "item_ids不能为空",
        "check_list.require" => "check_list不能为空",
        "check_list.array" => "check_list格式错误",
        "maintainer_id.require" => "maintainer_id不能为空",
        "field.require" => "图片字段名不能为空",
    "file_content.require" => "file_content不能为空",
    "per_row.integer" => "per_row格式错误",
        "staff_code.require" => "巡检账号不能为空",
        "staff_code.regex" => "巡检账号必须为首位非0的6位数字",
        "date.require" => "VReceive.date_require",
        "machine_usage.require" => "VReceive.machine_usage_require",
        "rsrp.require" => "信号强度不能为空",
        "sinr.require" => "信噪比不能为空",

    ];

    protected $scene = [
        "login" => ["msg_id","machine_id","timestamp","sign","account","password"],
        "wxLogin" => ["msg_id","machine_id","timestamp","sign"],
        "logout" => ["msg_id","machine_id","timestamp","sign"],

        "getIp" => ["msg_id","machine_id","timestamp","sign"],
        "getSystemInfo" => ["msg_id","machine_id","timestamp","sign"],
        "getMachine" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineLangList" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineChannel" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineGoods" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineInfo" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineConfig" => ["msg_id","machine_id","timestamp","sign"],
        "getPayTypeList" => ["msg_id","machine_id","timestamp","sign"],
        "getCalibrationConfig" => ["msg_id","machine_id","timestamp","sign"],
        "getAppSettings" => ["msg_id","machine_id","timestamp","sign"],
        "getTopicPage" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineConfigLangList" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineOnOff" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineHelp" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineView" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineViewList" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineVersionPlan" => ["msg_id","machine_id","timestamp","sign"],
        "reportMachineVersionDownload" => ["msg_id","machine_id","timestamp","sign","mvp_id","download_progress"],

        "getGoods" => ["msg_id","machine_id","timestamp","sign"],
        "submitRefundGoodsLog" => ["msg_id","machine_id","timestamp","sign","mobile","input_code","pic_out_goods_box","video_out_goods_box","video_refund_goods"],
        "getAdv" => ["msg_id","machine_id","timestamp","sign"],
        "playAdv" => ["msg_id","machine_id","timestamp","sign","adv_id","play_time"],
        "reportAdvDownload" => ["msg_id","machine_id","timestamp","sign","adv_id","download_progress"],
        "subCar" => ["msg_id","machine_id","timestamp","sign","pay_type","pay_method"],

        "subChannel" => ["msg_id","machine_id","timestamp","sign"],

        "subMachineGoods" => ["msg_id","machine_id","timestamp","sign","mgList"],

        "replenishment" => ["msg_id","machine_id","timestamp","sign","operator","repList"],
        "repList" => ["mc_id","quantity"],
        "changeChannelGoods" => ["msg_id","machine_id","timestamp","sign","mc_id","g_id","mg_id","quantity"],
        "uploadMedia" => ["msg_id","machine_id","timestamp","sign"],

        "getCouponList" => ["msg_id","machine_id","timestamp","sign"],
        "getCoupon" => ["msg_id","machine_id","timestamp","sign"],

        "getPickList" => ["msg_id","machine_id","timestamp","sign"],
        "getPick" => ["msg_id","machine_id","timestamp","sign"],

        "getCheckStockImg" => ["msg_id","machine_id","timestamp","sign","manager_id"],

        "getGoodsFind" => ["msg_id","machine_id","timestamp","sign","g_id"],

        "getLotteryList" => ["msg_id","machine_id","timestamp","sign"],

        "getLotteryOrder" => ["msg_id","machine_id","timestamp","sign","pay_type","pay_method","total_price","total_quantity","alc_id"],
        "getLuckyDraw" => ["msg_id","machine_id","timestamp","sign","order_id"],

        "getLotteryOutGoods" => ["msg_id","machine_id","timestamp","sign","order_id"],

        "getFd" => ["msg_id","machine_id","timestamp","sign"],

        "useFd" => ["msg_id","machine_id","timestamp","sign","order_id","fd_id"],
        "usePickCode" => ["msg_id","machine_id","timestamp","sign","pick_code"],
        "useCoupon" => ["msg_id","machine_id","timestamp","sign","coupon_code"],
        "useRevenueCoupon" => ["msg_id","machine_id","timestamp","sign","order_id","coupon_code"],

        "unclaimed" => ["msg_id","machine_id","timestamp","sign","order_id","details"],


        "resetMachine" => ["msg_id","machine_id","timestamp","sign","manager_id"],
        "getGoodsMultiple" => ["msg_id","machine_id","timestamp","sign"],

        "subGoodsMultiple" => ["msg_id","machine_id","timestamp","sign","pay_type","pay_method","mobile","gm_id","carList","hotel"],
        "carList" => ["gmg_id","quantity"],
        "hotel" => ["pay_amount","hotelId","roomId","num","adults","totalPrice","checkInDate","checkOutDate","nightly"],
        "nightly" => ["effectiveDate","amount"],

        "logoutH5" => ["msg_id","machine_id","timestamp"],

        "receipt" => ["msg_id","machine_id","timestamp","order_id"],
        "httpHeartbeat" => ["msg_id","machine_id","timestamp","sign"],
        "reportScreenImg" => ["msg_id","machine_id","timestamp","sign"],
        "requireOutGoods" => ["msg_id","machine_id","timestamp","sign","trade_no"],
        "getOrderPayStatus" => ["msg_id","machine_id","timestamp","sign"],
        "setHttpOutStatus" => ["msg_id","machine_id","timestamp","sign","trade_no","http_out_status"],
        // "triggerOutGoodsByHttp" => ["msg_id","machine_id","timestamp","sign","trade_no"],

        "cardAddPoints" => ["msg_id","machine_id","timestamp","card_no"],
        "checkBalancePayPassword" => ["msg_id","machine_id","timestamp","sign","card_no"],
        "getCardChangeLogs" => ["msg_id","machine_id","timestamp"],

        "getWcSmSCode" => ["msg_id","machine_id","timestamp","phone"],
        "wcLoginUser" => ["msg_id","machine_id","timestamp","phone","code"],
        "getWcLatestLoginInfo" => ["msg_id","machine_id","timestamp","sign","last_login_info_id"],
        "wcUserAddPoints" => ["msg_id","machine_id","timestamp","token","trade_no"],
        "wcPointsQrcode" => ["msg_id","machine_id","timestamp","token","trade_no"],

        "createdCardSaleOrder" => ["msg_id","machine_id","timestamp","sign","pay_type","pay_method"],
        "getWcGoodsLists" => ["msg_id","machine_id","timestamp","sign"],
        "getWcMCLists" => ["msg_id","machine_id","timestamp","sign"],
        "getWcUserInfo" => ["msg_id","machine_id","timestamp","sign"],

        "getLoginWcQrCode" => ["msg_id","machine_id","timestamp","sign"],
        
        

        "getMachineRentOrgLists" => ["msg_id","machine_id","timestamp","sign"],
        "getRentOrgGoodsLists" => ["msg_id","machine_id","timestamp","sign"],
        "reportSimCardMachineUsage" => ["msg_id","machine_id","timestamp","sign","date","machine_usage"],
        "reportSimSignal" => ["msg_id","machine_id","timestamp","sign","rsrp","sinr"],

        "searchWCGoods" => ["msg_id","machine_id","timestamp","name"],
        "sendError" => ["msg_id","machine_id","timestamp","sign","errorCode"],

        "getMaintenanceItems" => ["msg_id","machine_id","timestamp","sign"],
    "submitMaintenanceRecord" => ["msg_id","machine_id","timestamp","sign","maintainer_id","check_list"],
        "getMaintenanceRecords" => ["msg_id","machine_id","timestamp","sign"],

    // 导入维护记录
    "importMaintenanceRecords" => ["msg_id","machine_id","timestamp","sign","file_content","per_row"],

        "getCheckListItems" => ["msg_id","machine_id","timestamp","sign"],
        "checkInspectionStaffCode" => ["msg_id","machine_id","timestamp","sign"],
        "submitCheckListRecord" => ["msg_id","machine_id","timestamp","sign","manager_id","check_list"],
        "getCheckListRecords" => ["msg_id","machine_id","timestamp","sign"],
    "importCheckListRecords" => ["msg_id","machine_id","timestamp","sign","file_content","per_row"],

        "testUploadInfoMq" => ["msg_id","machine_id","timestamp"],
    ];

    /**
     * 验证时间戳
     * @param $item
     * @return bool|string
     */
    public function checkTimestamp($item)
    {
        $scene = $this->currentScene;
        // 非uploadMedia的场景才需要验证时间戳
        if ($scene != "uploadMedia") {
            if (!$item) return "时间戳不能为空";
            $tolerance = intval(config('rabbit_mq.machine_receive_timestamp_tolerance') ?: 180);
            if ($tolerance < 120) $tolerance = 120;
            if (time() - intval($item) > $tolerance) return "VReceive.timestamp_checkTimestamp_overdue";
        }
        return true;
    }
}
