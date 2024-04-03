<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 17:51
 */

namespace app\management\validate;


class VMachineHelp extends VCommon
{
    protected $rule = [
        "mh_id" => "require",
        "lang" => "require",
        "title" => "require",
        "content" => "require",
        "m_id" => "require",
        "machine_id" => "require",
        "help_list" => "require",
    ];

    protected $message = [
        "mh_id.require" => "VMachineInfo.mc_id_require",
        "m_id.require" => "VMachineInfo.m_id_require",
        "machine_id.require" => "VMachineInfo.machine_id_require",
        "lang.require" => "VMachineInfo.lang_require",
        "title.require" => "VMachineInfo.title_require",
        "content.require" => "VMachineInfo.content_require",
        "help_list.require" => "VMachineInfo.help_list_require",
    ];

    protected $scene = [
        "getList" => ["m_id"],
        "add" => ["m_id","machine_id","help_list"],
        "addMore" => ["title","content","lang"],
        "update" => ["mh_id"],
        "updateMore" => ["help_list"],
        "del" => ["mh_id"],
    ];
}