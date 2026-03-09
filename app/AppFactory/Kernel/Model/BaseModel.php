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

    public static $singlePathFieldList = ["path","pic","button_pic","bg_pic","file_path","icon","ico","gm_pic","qr_code","receipt_code1","receipt_code2","receipt_code3"
        ,"discount_pic","screen_img","camera_img","exchange_img","transaction_video"];
    public static $morePathFieldList = [
        ['field' => "banner","separator" => ";"],
        ['field' => "details_pic","separator" => ";"],
        ['field' => "deliver_pics","separator" => ","]
    ];

    public static $richTextPathFieldList = ["desc","gm_desc"];


    /**
     * 修改操作前
     * 带域名的数据全部清除域名
     * @param Model $model
     * @return mixed|void
     */
    public static function onBeforeWrite(Model $model)
    {
        $host = env("app.host");
        // 清除单路径域名信息
        foreach (self::$singlePathFieldList as $key => $value) {
            if (isset($model->$value)) {
                $model->$value = str_replace($host, '', $model->$value);
            }
        }
        // 清除多路径域名信息
        foreach (self::$morePathFieldList as $mK => $mV) {
            $field = $mV['field'];
            if (isset($model->$field)) {
                $list = explode($mV['separator'], $model->$field);
                $temp = [];
                foreach ($list as $lV) {
                    $temp[] = str_replace($host, '', $lV);
                }
                if ($temp) $model->$field = implode($mV['separator'], $temp);
            }
        }
        // 清除富文本路径域名信息
        foreach (self::$richTextPathFieldList as $rK => $rV) {
            if (isset($model->$rV)) {
                $richList = getImagesFromRichText($model->$rV);
                if ($richList) {
                    foreach ($richList as $richV) {
                        $new = str_replace($host,'',$richV);
                        if (strpos($new,'/api') === 0 || strpos($new,'api') === 0) {
                            $new = str_replace("/api",'',$new);
                            $new = str_replace("api",'',$new);
                        }
                        $model->$rV = str_replace($richV,$new,$model->$rV);
                    }
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
        // 增加单路径域名信息
        foreach (self::$singlePathFieldList as $key => $value) {
            if (isset($model->$value)) {
                if (preg_match('/\.([a-zA-Z0-9]+)$/',$model->$value,$matches)) {
                    $extension = $matches[1];
                    if (in_array($extension,["jpg","jpeg","png","gif","mp4","mp3","JPG","JPEG","PNG","GIF","MP4","MP3"])) {
                        $model->$value = checkStrDomain($model->$value);
                    }
                }
            }
        }
        // 增加多路径域名信息
        foreach (self::$morePathFieldList as $mK => $mV) {
            $field = $mV['field'];
            if (isset($model->$field)) {
                $list = explode($mV['separator'], $model->$field);
                $temp = [];
                foreach ($list as $lV) {
                    $temp[] = checkStrDomain($lV);
                }
                if ($temp) $model->$field = implode($mV['separator'], $temp);
            }
        }
        // 增加富文本路径域名信息
        foreach (self::$richTextPathFieldList as $rK => $rV) {
            if (isset($model->$rV)) {
                $richList = getImagesFromRichText($model->$rV);
                if ($richList) {
                    foreach ($richList as $richV) {
                        if (strpos($richV,'/api') === 0 || strpos($richV,'api') === 0) {
                            $richV = str_replace("/api",'',$richV);
                            $richV = str_replace("api",'',$richV);
                        }
                        $new = checkStrDomain($richV);
                        $model->$rV = str_replace($richV,$new,$model->$rV);
                    }
                }
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
     * @param $where
     * @param int|array $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFn
     * @param string $group
     * @param int $limit
     * @return BaseModel|BaseModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getList($where,$pageNum = null,$field = "*",$order = "",$eachFn = "",$group = "",$limit = 0)
    {
//        try {
            if(isset($where['ao_id']) && $where['ao_id'] < 2){
                unset($where['ao_id']);
            }
            $fields = array_column(Db::query("SHOW COLUMNS FROM " . self::getTable()), 'Field');
            if (in_array('creator', $fields) && (strpos($field,"*") !== false || strpos($field, "creator") !== false)) {
                $field .= ", (SELECT nickname FROM auth_manager au WHERE au.manager_id = a.creator) creator_nickname";
            }
            if (in_array('ao_id', $fields) && (strpos($field,"*") !== false || strpos($field, "ao_id") !== false)) {
                $field .= ", (SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = a.ao_id) organization_name";
            }
            if (isset($pageNum) && $pageNum && !is_numeric($pageNum) && !is_array($pageNum)) throw new \Exception("页面数据条数必须为数字或数组");
            if(isset($where['raw'])){
                $whereRaw = $where['raw'];
                unset($where['raw']);
                // if(isset($where['ao_id'])) unset($where['ao_id']);
                $model = self::alias("a")->where($where)->whereRaw($whereRaw)->field($field)->order($order);
            }else{
                $model = self::alias("a")->where($where)->field($field)->order($order);
            }
            if ($group) $model = $model->group($group);
            if ($limit) $model = $model->limit($limit);
            // $model->select();
            // dd($model->getLastSql());
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

        /**
     * @param $where
     * @param int|array $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFn
     * @param string $group
     * @param int $limit
     * @param array $with
     * @return BaseModel|BaseModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getListAndWith($where,$pageNum = null,$field = "*",$order = "",$eachFn = "",$group = "",$limit = 0,$with = [])
    {
            if(isset($where['ao_id']) && $where['ao_id'] < 2){
                unset($where['ao_id']);
            }
            $fields = array_column(Db::query("SHOW COLUMNS FROM " . self::getTable()), 'Field');
            if (in_array('creator', $fields) && (strpos($field,"*") !== false || strpos($field, "creator") !== false)) {
                $field .= ", (SELECT nickname FROM auth_manager au WHERE au.manager_id = a.creator) creator_nickname";
            }
            if (in_array('ao_id', $fields) && (strpos($field,"*") !== false || strpos($field, "ao_id") !== false)) {
                $field .= ", (SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = a.ao_id) organization_name";
            }
            if (isset($pageNum) && $pageNum && !is_numeric($pageNum) && !is_array($pageNum)) throw new \Exception("页面数据条数必须为数字或数组");
            if(isset($where['raw'])){
                $whereRaw = $where['raw'];
                unset($where['raw']);
                // if(isset($where['ao_id'])) unset($where['ao_id']);
                $model = self::alias("a")->where($where)->whereRaw($whereRaw)->field($field)->order($order);
            }else{
                $model = self::alias("a")->where($where)->field($field)->order($order);
            }
            if ($with) $model = $model->with($with);
            if ($group) $model = $model->group($group);
            if ($limit) $model = $model->limit($limit);
            // $model->select();
            // dd($model->getLastSql());
            if (!$pageNum) return $model->select();
            $model = $model->paginate($pageNum, false, ["query" => request()->param()]);
            if ($eachFn && is_callable($eachFn)) {
                $model = $model->each($eachFn);
            }
            return $model;
    }
}