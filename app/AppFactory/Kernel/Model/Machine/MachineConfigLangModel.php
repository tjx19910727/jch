<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 15:01
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineConfigLangModel extends BaseModel
{
    protected $pk = "mcl_id";
    protected $name = "machine_config_lang";
    protected $schema = [
        "mcl_id" => "int",
        "m_id" => "int",
        "machine_id" => "string",
        "mc_id" => "int",
        "lang" => "string",
        "note_model" => "int",
        "receipt_code1" => "string",
        "receipt_code2" => "string",
        "receipt_code3" => "string",
        "receipt_desc" => "string",
        "deal_success_title" => "string",
        "deal_success_sub_title" => "string",
        "deal_abnormal_pic" => "string",
        "deal_fail_title" => "string",
        "deal_fail_sub_title" => "string",
        "claim_goods_title" => "string",
        "out_goods_title" => "string",
        "discount_show" => "int",
        "discount_pic" => "string",
        "buy_normal_tab" => "string",
        "buy_fix_tab" => "string",
        "buy_hotel_tab" => "string",
        "buy_tab_sort" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int"
    ];
}