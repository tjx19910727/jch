<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 15:27
 */

namespace app\management\validate;


class VTemplateLayout extends VCommon
{

        protected $rule = [
            "id" => "require",
            "name" => "require",
            "template_id" => "require",
            "height" => "require",
            "width" => "require",
            "left" => "require",
            "top" => "require",
        ];

        protected $message = [
            "id.require" => "VTemplateLayout.id_require",
            "name.require" => "VTemplateLayout.name_require",
            "template_id.require" => "VTemplateLayout.template_id_require",
            "height.require" => "VTemplateLayout.height_require",
            "width.require" => "VTemplateLayout.width_require",
            "left.require" => "VTemplateLayout.left_require",
            "top.require" => "VTemplateLayout.top_require",
        ];

        protected $scene = [
            "query" => ["template_id"],
            "add" => ["name","template_id","height","width","left","top"],
            "update" => ["id"],
            "del" => ["id"],
        ];
}