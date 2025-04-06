<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 10:31
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineVersion extends VCommon
{
    protected $rule = [
        "mv_id" => "require",
        "version_no" => "require|max:100",
        "path" => "require|max:255",
        "size" => "require",
        "desc" => "max:10000",
    ];

    protected $message = [
        "mv_id.require" => "VMachineVersion.mv_id_require",
        "version_no.require" => "VMachineVersion.version_no_require",
        "version_no.max" => "VMachineVersion.version_no_max",
        "path.require" => "VMachineVersion.path_require",
        "path.max" => "VMachineVersion.path_max",
        "size.require" => "VMachineVersion.size_require",
        "desc.max" => "VMachineVersion.desc_max",
    ];

    protected $scene = [
        "add" => ["version_no","path","size","desc"],
        "del" => ["mv_id"],
    ];

    public function sceneUpdate()
    {
        return self::only(['mv_id','version_no','path','desc'])
            ->remove("version_no","require")
            ->remove("path","require");

    }
}