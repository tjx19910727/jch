<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 19:58
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineLang extends VCommon
{

    protected $rule = [
        "ml_id" => "require",
        "m_id" => "require",
        "machine_id" => "require",
        "lang" => "require",
    ];

    protected $message = [
        "ml_id.require" => "VMachineLang.ml_id_require",
        "m_id.require" => "VMachineLang.m_id_require",
        "machine_id.require" => "VMachineLang.machine_id_require",
        "lang.require" => "VMachineLang.lang_require",
    ];

    protected $scene = [
        "add" => ["m_id","machine_id","lang"],
        "update" => ["ml_id"],
        "updateMore" => ["ml_id"],
        "del" => ["ml_id"],
    ];
}