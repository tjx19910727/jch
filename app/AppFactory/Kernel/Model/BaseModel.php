<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 15:43
 */

namespace app\AppFactory\Kernel\Model;


use app\AppFactory\Kernel\Traits\ModelTrait;
use think\Model;

class BaseModel extends Model
{
    use ModelTrait;

    protected $createTime = "create_time";
    protected $updateTime = "update_time";

    public static function getFind($where,$field = '*',$order = "")
    {
        $result = self::where($where)->field($field)->order($order)->find();
        return $result;
    }

    public static function insertOneGetId($insert)
    {
        $result = self::insertGetId($insert);
        return $result;
    }

    /**
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFn
     * @param string $group
     */
    public static function getList($where,$pageNum = 0,$field = "*",$order = "",$eachFn = "",$group = "",$limit = 0)
    {
        if (!is_numeric($pageNum)) throw new \Exception("页面数据条数必须为数字");
        $model = self::where($where)->field($field)->order($order);
        if ($group) $model = $model->group($group);
        if ($limit) $model = $model->limit($limit);
        if (!$pageNum) return $model->select();
        $model = $model->paginate($pageNum,false,["query" => request()->param()]);
        if (is_callable($eachFn)) {
            $model = $model->each($eachFn);
        }
        return $model;
    }

    /**
     * 数据条数
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public static function getCount($where)
    {
        return self::where($where)->count();
    }

    public static function getColumn($where,$column)
    {
        return self::where($where)->column($column);
    }

    public static function getFieldValue($where,$value,$order = "")
    {
        return self::where($where)->order($order)->value($value);
    }

    public static function getSum($where,$sum)
    {
        return self::where($where)->sum($sum);
    }

    public static function setInc($where,$field,$inc = 1)
    {
        return self::where($where)->inc($field,$inc)->update();
    }

    public static function setDec($where,$field,$dec = 1)
    {
        return self::where($where)->dec($field,$dec)->update();
    }

    public static function getLS()
    {
        return self::getLastSql();
    }
}