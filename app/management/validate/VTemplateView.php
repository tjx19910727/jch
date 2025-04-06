<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 15:26
 */

namespace app\management\validate;


class VTemplateView extends VCommon
{

    protected $rule = [
        "id" => "require",
        "name" => "require",
        "template_id" => "require",
        "height" => "require",
        "width" => "require",
        "plugin_data" => "require",

        "left" => "require",
        "top" => "require",
        "layout_id" => "require",
    ];

    protected $message = [
        "id.require" => "VTemplateView.id_require",
        "name.require" => "VTemplateView.name_require",
        "template_id.require" => "VTemplateView.template_id_require",
        "height.require" => "VTemplateView.height_require",
        "width.require" => "VTemplateView.width_require",
        "plugin_data.require" => "VTemplateView.plugin_data_require",

        "layout_id.require" => "VTemplateView.layout_id_require",
        "left.require" => "VTemplateView.left_require",
        "top.require" => "VTemplateView.top_require",
    ];

    protected $scene = [
        "add" => ["name","template_id","height","width","plugin_data"],
        "update" => ["id"],
        "del" => ["id"],

        "plugin_data" => ['layout_id','height','width','left','top'],
        "copy" => ["id","name"],
    ];
}