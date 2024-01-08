<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 15:14
 */

namespace app\management\validate;


class VAdvertisement extends VCommon
{
    protected $rule = [
        // 广告素材
        "res_id" => "require",
        "title" => "require|max:100",
        "file_path" => "require|max:255",
        "status" => "require",

        // 广告推送
        "adv_id" => "require",
        "adv_title" => "require",
        "duration_time" => "require",
        "total_times" => "require",
        "store_id" => "require",
        "start_date" => "require",
        "end_date" => "require",
        "time_list" => "require",
        "screen" => "require",
        "screen_full" => "require",

    ];

    protected $message = [
        // 广告素材
        "res_id.require" => "素材ID不能为空",
        "title.require" => "素材标题不能为空",
        "title.max" => "素材标题长度超过限制",
        "file_path.require" => "上传文件不能为空",
        "file_path.max" => "上传文件路径长度超过限制",
        "status.require" => "状态不能为空",

        // 广告推送
        "adv_id.require" => "推送广告ID不能为空",
        "adv_title.require" => "广告推送标题不能为空",
        "duration_time.require" => "播放时长不能为空",
        "total_times.require" => "总播放次数不能为空",
        "store_id.require" => "请选择门店",
        "start_date.require" => "日期列表不能为空",
        "end_date.require" => "日期列表不能为空",
        "time_list.require" => "时间段列表不能为空",
        "screen.require" => "请选择屏幕",
        "screen_full.require" => "请选择是否全屏",
    ];

    protected $scene = [
        "addRes" => ["title","file_path","status"],
        "addPush" => ["adv_title","res_id","duration_time","total_times","store_id","start_date","end_date","time_list","screen","screen_full"],
        "updateRes" => ["res_id"],
        "updatePush" => ["adv_id"],
        "upDown" => ["adv_id","status"],
    ];
}