<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 11:46
 */

namespace app\AppFactory\Kernel\Traits;


use think\facade\Db;

trait DbTrait
{

    /**
     * 开启事务
     */
    public function startTrans()
    {
        Db::startTrans();
    }

    /**
     * 提交事务
     */
    public function commitTrans()
    {
        Db::commit();
    }

    /**
     * 回滚事务
     */
    public function rollbackTrans()
    {
        Db::rollback();
    }

    /**
     * 检查事务
     * @param $flag
     * @return int
     */
    public function checkFlag($flag)
    {
        return flag_check($flag);
    }

    /**
     * 判断提交或结束事务
     * @param $result
     * @param int $rAction
     * @return bool|string
     */
    public function checkTrans($result,$rAction = 1)
    {
        $return = true;
        if ($result) {
            Db::commit();
        } else {
            Db::rollback();
            $return = false;
        }
        if ($rAction) return $this->rAction($return);
        return $return;
    }

    public function getLS()
    {
        return Db::getLastSql();
    }

}