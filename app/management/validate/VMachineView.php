<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 15:51
 */

namespace app\management\validate;


class VMachineView extends VCommon
{

        protected $rule = [
            "mv_id" => "require",
            "template_id" => "require",
            "view_id" => "require",
            "m_id" => "require",
            "name" => "require",
            "publish_time" => "require",
        ];

        protected $message = [
            "ml_id.require" => "VMachineView.mv_id_require",
            "template_id.require" => "VMachineView.template_id_require",
            "view_id.require" => "VMachineView.view_id_require",
            "m_id.require" => "VMachineView.m_id_require",
            "name.require" => "VMachineView.name_require",
            "publish_time.require" => "VMachineView.publish_time_require",
        ];

        protected $scene = [
            "addMore" => ["template_id","view_id","m_id","name"],
            "update" => ["mv_id"],
            "del" => ["mv_id"],
        ];
}