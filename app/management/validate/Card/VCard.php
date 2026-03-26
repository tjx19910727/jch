<?php
/**
 * Created by VSCode.
 * User: Alex-jixinag
 * Date: 2025/12/08
 * Time: 16:54
 */

namespace app\management\validate\Card;


use app\management\validate\VCommon;

class VCard extends VCommon
{
    protected $rule = [
        "card_no" => "require|neq:0",
        "card_show_no" => "require|neq:0",
        "change_type" => "in:1,2",
        "points_changed" => "require",
        "balance_changed" => "require",
        "password" => "require",
        "confirm_password" => "require",
    ];

    protected $message = [
        "card_no.require" => "VCard.card_no_require",
        "card_no.neq" => "VCard.card_no_neq",
        "card_show_no.require" => "VCard.card_show_no_require",
        "card_show_no.neq" => "VCard.card_show_no_neq",
        "points_changed.require" => "VCard.points_changed_require",
        "balance_changed.require" => "VCard.balance_changed_require",
        "change_type.in" => "VCard.change_type_in",
        "password.require" => "VCard.password_require",
        "confirm_password.require" => "VCard.confirm_password_require",

    ];

    protected $scene = [
        "add" => ["card_no",'points_changed','change_type'],
        "add_2" => ["card_no",'card_show_no'],
        "addBalance" => ["card_no",'balance_changed','change_type'],
        "update" => ["id"],
        "del" => ["id"],
        "changePwd" => ["card_no", "password", "confirm_password"],
    ];
}