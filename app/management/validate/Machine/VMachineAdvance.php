<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/29
 * Time: 10:24
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineAdvance extends VCommon
{
    protected $rule = [
        // 广告推送
        "adv_id" => "require",
        "adv_title" => "require",
        "duration_time" => "require",
        "total_times" => "require",
        "m_id" => "require",
        "start_date" => "require",
        "end_date" => "require",
        "time_list" => "require",
        "screen" => "require",
        "screen_full" => "require",
        "push_type" => "require|in,(2,3)",
    ];

    protected $message = [
        // 广告推送
        "adv_id.require" => "VAdvertisement.adv_id_require",
        "adv_title.require" => "VAdvertisement.adv_title_require",
        "duration_time.require" => "VAdvertisement.duration_time_require",
        "total_times.require" => "VAdvertisement.total_times_require",
        "m_id.require" => "VAdvertisement.m_id_require",
        "start_date.require" => "VAdvertisement.start_date_require",
        "end_date.require" => "VAdvertisement.end_date_require",
        "time_list.require" => "VAdvertisement.time_list_require",
        "screen.require" => "VAdvertisement.screen_require",
        "screen_full.require" => "VAdvertisement.screen_full_require",
        "push_type.require" => "VAdvertisement.push_type_require",
        "push_type.in" => "VAdvertisement.push_type_in",
    ];

    protected $scene = [
        "addPush" => ["adv_title","res_id","duration_time","total_times","m_id","start_date","end_date","time_list","screen","screen_full","push_type"],
        "updatePush" => ["adv_id"],
        "upDown" => ["status"],
    ];
}