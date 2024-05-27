<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/23
 * Time: 14:20
 */

return [
    // 忽略记录的API
    "ignore" => [
        // 设备上报 /machine/receive，校对方法名
        "machine" => [
            "getSystemInfo",
            "getMachine",
            "getMachineChannel",
            "getMachineGoods",
            "getMachineInfo",
            "getMachineOnOff",
            "getMachineConfig",
            "getMachineHelp",
            "getMachineView",
            "getMachineVersionPlan",
            "getGoods",
            "getGoodsFind",
            "getAdv",
            "playAdv",
            "subCar",
            "uploadMedia",
            "getCheckStockImg",
            "getCouponList",
            "getCoupon",
            "getPickList",
            "getPick",
            "getLotteryList",
            "getLotteryOrder",
            "getLuckyDraw",
            "getLotteryOutGoods",
            "getFd",
            "useFd",
            "getIp",
            "unclaimed",
        ],

        // 管理端后台，/management/控制器名/方法名，只校对方法名
        "management" => [
            // 普通查列表、查单条
            "getList",
            "getFind",

            // 查分组
            "getGroupList",

            // 查全球地区信息
            "getContinentsList",
            "getCountriesList",
            "getStatesList",
            "getCitiesList",
            "getRegionsList",
            "getAreaList",

            // 查询互动商品点击列表
            "getHitList",

            // 获取设备货架照片
            "getChannelImg",

            // 获取设备照片
            "getImg",

            // 查询订单详情
            "getDetails",
            // 商品交易列表
            "getDetailsList",
            // 获取订单退款列表
            "getRefundList",
            // 获取销售报表概况
            "getReport",
            "getReportList",
            // 获取当前登录的账号信息
            "getMineInfo",
            "getSelfRoleNode",
            "checkPwd",

            // 首页概览统计接口
            "getSaleData",
            "getMachineData",
            "getChannelData",
            "getEmptyChannel",
            "getBadChannel",
            "getStockOutChannel",
            "getSaleChart",
            "getMachine10List",
            "getGoods10List",

            // 获取验证码
            "getCaptcha",
        ],
        "mobile" => [
            "checkScan",
            "getMachine",
            "getChannel",
            "getMachineGoods",
        ],
    ],
    "replace" => [

    ],
];