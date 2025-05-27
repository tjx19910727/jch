<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/6
 * Time: 14:42
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthOrganizationModel extends BaseModel
{
    protected $pk = "ao_id";
    protected $name = "auth_organization";

    protected $schema = [
        "ao_id" => "int",
        "pid" => "int",
        "level" => "int",
        "organization_name" => "string",
        "is_top" => "int",
        "sort" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];

    public static function getPAoIds($where,&$ids = [])
    {
        $pid = self::where($where)->value("pid");
        if ($pid && $pid != 0) {
            $ids[] = $pid;
            self::getPAoIds(['ao_id' => $pid],$ids);
        }
    }

    public static function getCAoIds($where,&$ids = [])
    {
        $cIds = self::where($where)->order("sort asc,update_time desc")->column("ao_id");
        if ($cIds) {
            foreach ($cIds as $v) {
                if ($v) {
                    $ids[] = $v;
                    self::getCAoIds(['pid' => $v], $ids);
                }
            }
        }
    }
}