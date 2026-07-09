<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/23
 * Time: 10:00
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VOtaVersion extends VCommon
{
    protected $rule = [
        "ov_id" => "require",
        "version_no" => "require|max:100",
        "path" => "require|max:255",
        "size" => "require",
        "desc" => "max:10000",
    ];

    protected $message = [
        "ov_id.require" => "VOtaVersion.ov_id_require",
        "version_no.require" => "VOtaVersion.version_no_require",
        "version_no.max" => "VOtaVersion.version_no_max",
        "path.require" => "VOtaVersion.path_require",
        "path.max" => "VOtaVersion.path_max",
        "size.require" => "VOtaVersion.size_require",
        "desc.max" => "VOtaVersion.desc_max",
    ];

    protected $scene = [
        "add" => ["version_no", "path", "size", "desc"],
        "del" => ["ov_id"],
    ];

    public function sceneUpdate()
    {
        return self::only(['ov_id', 'version_no', 'path', 'desc'])
            ->remove("version_no", "require")
            ->remove("path", "require");
    }
}
