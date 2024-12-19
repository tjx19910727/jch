<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 15:43
 */

namespace app\AppFactory\Kernel\Model;


use app\AppFactory\Kernel\Traits\ModelTrait;
use think\facade\Db;
use think\Model;

class BaseModel extends Model
{
    use ModelTrait;

    protected $createTime = "create_time";
    protected $updateTime = "update_time";

    /**
     * 修改操作前
     * 带域名的数据全部清除域名
     * @param Model $model
     * @return mixed|void
     */
    public static function onBeforeWrite(Model $model)
    {
        $host = env("app.host");
        if (isset($model->pic)) {
            $model->pic = str_replace($host, '', $model->pic);
        }
        if (isset($model->path)) {
            $model->path = str_replace($host, '', $model->path);
        }
        if (isset($model->banner)) {
            $banner = explode(";",$model->banner);
            $temp = [];
            foreach ($banner as $v) {
                $temp[] = str_replace($host, '', $v);
            }
            if ($temp) $model->banner = implode(";",$temp);
        }
        if (isset($model->details_pic)) {
            $details = explode(";",$model->details_pic);
            $detailsTemp = [];
            foreach ($details as $dV) {
                $detailsTemp[] = str_replace($host, '', $dV);
            }
            if ($detailsTemp) $model->details_pic = implode(";", $detailsTemp);
        }
        if (isset($model->desc)) {
            $descList = getImagesFromRichText($model->desc);
            if ($descList) {
                foreach ($descList as $dV2) {
                    $new = str_replace($host, '', $dV2);
                    $model->desc = str_replace($dV2, $new, $model->desc);
                }
            }
        }
    }

    /**
     * 查询之后操作
     * @param Model $model
     */
    public static function onAfterRead(Model $model)
    {
        if (isset($model->path)){
            $model->path = checkStrDomain($model->path);
        }
        if (isset($model->pic)) {
            $model->pic = checkStrDomain($model->pic);
        }
        if (isset($model->banner)) {
            $banner = explode(";",$model->banner);
            $temp = [];
            foreach ($banner as $v) {
                $temp[] = checkStrDomain($v);
            }
            if ($temp) $model->banner = implode(";",$temp);
        }
        if (isset($model->details_pic)) {
            $details = explode(";",$model->details_pic);
            $detailsTemp = [];
            foreach ($details as $dV) {
                $detailsTemp[] = checkStrDomain($dV);
            }
            if ($detailsTemp) $model->details_pic = implode(";", $detailsTemp);
        }
        if (isset($model->desc)) {
            $descList = getImagesFromRichText($model->desc);
            foreach ($descList as $dV2) {
                $new = checkStrDomain($dV2);
                $model->desc = str_replace($dV2,$new,$model->desc);
            }
        }
    }





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
//        try {
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
            if ($eachFn && is_callable($eachFn)) {
                $model = $model->each($eachFn);
            }
            return $model;
//        } catch (DataNotFoundException $e) {
//            actionException($e,1);
//            return $e->getMessage();
//        } catch (ModelNotFoundException $e) {
//            actionException($e,1);
//            return $e->getMessage();
//        } catch (DbException $e) {
//            actionException($e,1);
//            return $e->getMessage();
//        } catch (\Exception $e) {
//            actionException($e,1);
//            return $e->getMessage();
//        }
    }

    /**
     * 数据条数
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public static function getCount($where,$field = "*")
    {
        return self::where($where)->count($field);
    }

    public static function getColumn($where,$column,$key = "")
    {
        return self::where($where)->column($column,$key);
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