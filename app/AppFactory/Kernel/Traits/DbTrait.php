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

    /**
     * 获取字段名与备注
     * @param string $table_name 表名，不传查全库所有表字段
     * @return mixed
     */
    public function getFieldComment($table_name = "")
    {
        $sql = "SELECT COLUMN_NAME as 'Field', COLUMN_COMMENT as 'Comment' 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_schema = 'kiosk' AND COLUMN_NAME <> 'create_time' AND COLUMN_NAME <> 'update_time'  AND COLUMN_NAME <> 'update_id' AND COLUMN_COMMENT is not null AND COLUMN_COMMENT <> ''";
        if ($table_name) $sql .= " AND table_name='$table_name'";
        $sql .= " group by COLUMN_NAME";
        $result = Db::query($sql);
        return $result;
    }

}