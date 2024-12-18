<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/11/11
 * Time: 11:27
 */

namespace app\AppFactory\Kernel\Model\Action;


use app\AppFactory\Kernel\Model\BaseModel;
use think\Model;

class ActionVideoModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "action_video";

    public static function onAfterRead(Model $model)
    {
        if ($model->path){
            $model->path = checkStrDomain($model->path);
        }
    }

    public static function onAfterDelete(Model $model)
    {
        if ($model->path) {
            $model->path = getAbsolutePath($model->path);
            if (file_exists($model->path) && is_file($model->path)) {
                @unlink($model->path);
            }
        }
    }
}