<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 15:43
 */

namespace app\AppFactory\Kernel\Model;


use app\AppFactory\Kernel\Traits\ModelTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\Model;

class BaseModel extends Model
{
    use ModelTrait;

    protected $createTime = "create_time";
    protected $updateTime = "update_time";


    /**
     * @param $where
     * @param string $field
     * @param string $order
     * @param string $group
     * @return mixed
     */
    public static function getFind($where,$field = '*',$order = "", $group = "")
    {
        $result = self::where($where)->field($field)->order($order)->group($group)->find();
        return $result;
    }

    public static function insertOneGetId($insert)
    {
        $result = self::insertGetId($insert);
        return $result;
    }

    /**
     * 删除
     * @param $where
     * @return bool
     */
    public static function whereDel($where)
    {
        return self::where($where)->delete();
    }

    /**
     * 查询列表
     * @param string|array $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFn
     * @param string $group
     * @param int $limit
     * @return BaseModel|BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public static function getList($where,$pageNum = 0,$field = "*",$order = "",$eachFn = "",$group = "",$limit = 0)
    {
        try {
            $fields = array_column(Db::query("SHOW COLUMNS FROM " . self::getTable()), 'Field');
            if (in_array('creator', $fields) && ($field == "*" || strpos($field, "creator") !== false)) {
                $field .= ", (SELECT nickname FROM auth_manager au WHERE au.manager_id = a.creator) creator_nickname";
            }
            if (in_array('ao_id', $fields) && ($field == "*" || strpos($field, "ao_id") !== false)) {
                $field .= ", (SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = a.ao_id) organization_name";
            }
            if (!is_numeric($pageNum)) throw new \Exception("页面数据条数必须为数字");
            $model = self::alias("a")->where($where)->field($field)->order($order);
            if ($group) $model = $model->group($group);
            if ($limit) $model = $model->limit($limit);
            if (!$pageNum) return $model->select();
            $model = $model->paginate($pageNum, false, ["query" => request()->param()]);
            if (is_callable($eachFn)) {
                $model = $model->each($eachFn);
            }
            return $model;
        } catch (DataNotFoundException $e) {
            actionException($e,1);
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            actionException($e,1);
            return $e->getMessage();
        } catch (DbException $e) {
            actionException($e,1);
            return $e->getMessage();
        } catch (\Exception $e) {
            actionException($e,1);
            return $e->getMessage();
        }
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