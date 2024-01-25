<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 11:39
 */

namespace app\machine\controller;


use app\AppFactory\Kernel\Model\Auth\AuthOrganizationModel;
use app\AppFactory\Kernel\Util\SignUtil;
use app\BaseController;
use think\facade\Db;

class Test extends BaseController
{
    public function testPid()
    {
        $sql = "
            WITH RECURSIVE cte AS (
                SELECT * FROM cf_auth_organization WHERE id = 3 -- 设置根节点ID
                
                UNION ALL
                
                SELECT t.* FROM cf_auth_organization t INNER JOIN cte ON t.id = cte.pid
            )
            SELECT * FROM cf_auth_organization cte;
        ";
        $result = Db::query($sql);
        dump($result);
        dump(Db::getLastSql());

        AuthOrganizationModel::getPAoIds(6,$ids);
        dump($ids);

        AuthOrganizationModel::getCAoIds(1,$cIds);
        dump($cIds);
    }
    public function testSign()
    {
        $data = [
            "machine_id" => "test0001",
//            "re_type" => "1",
//            "ac_type" => "1",
            "timestamp" => time(),
        ];
        $data['sign'] = SignUtil::makeSign($data,"1e9cf702b9a561e183e6fc450b243262");
        dump($data);
        dump(json_encode($data,320));
    }
}