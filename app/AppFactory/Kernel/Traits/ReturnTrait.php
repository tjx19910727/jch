<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:54
 */

namespace app\AppFactory\Kernel\Traits;


use think\facade\Lang;

trait ReturnTrait
{
    /**
     * 自定义返回结果
     * @param $state
     * @param string $msg
     * @param array $data
     * @param bool $isJson
     * @return array|\think\response\Json
     */
    public function r($state,$msg = "",$data = [],$isJson = true)
    {
        return returnState($state,$msg,$data,$isJson);
    }

    /**
     * 查询返回结果
     * @param $data
     * @return array|\think\response\Json
     */
    public function rQ($data)
    {
        $msg = Lang::get("query_success");
        if (!$data) {
            $msg = Lang::get("query_fail");
        }
        return returnState(200,$msg,$data);
    }

    /**
     * 添加返回结果
     * @param $data
     * @return array|\think\response\Json
     */
    public function rA($data)
    {
        return returnData($data, Lang::get("add_success") . "|" . Lang::get("add_fail"));
    }

    /**
     * 修改返回结果
     * @param $data
     * @return array|\think\response\Json
     */
    public function rU($data)
    {
        return returnData($data, Lang::get("update_success") . "|" . Lang::get("update_fail"));
    }

    /**
     * 删除返回结果
     * @param $data
     * @return array|\think\response\Json
     */
    public function rD($data)
    {
        return returnData($data, Lang::get("del_success") . "|" . Lang::get("del_fail"));
    }

    /**
     * 操作返回结果
     * @param $data
     * @return array|\think\response\Json
     */
    public function rAction($data)
    {
        return returnData($data, Lang::get("action_success") . "|" . Lang::get("action_fail"));
    }

    /**
     * 复制返回结果
     * @param $data
     * @return array|\think\response\Json
     */
    public function rCopy($data)
    {
        return returnData($data, Lang::get("copy_success") . "|" . Lang::get("copy_fail"));
    }

    /**
     * 查无数据
     * @return array|\think\response\Json
     */
    public function rNoData()
    {
        return returnState(100, Lang::get("query_fail"));
    }

    /**
     * 返回异常抛出信息
     * @param $msg
     * @return array|\think\response\Json
     */
    public function rTryCatch($msg)
    {
        return returnTryCatch($msg);
    }

    /**
     * 返回失败
     * @param string $msg
     * @return array|\think\response\Json
     */
    public function rFail($msg = "")
    {
        $return = Lang::get("action_fail");
        if ($msg) $return = $return . ":" . arr2json($msg);
        return returnState(100,$return);
    }

    /**
     * 返回成功
     * @param string $msg
     * @return array|\think\response\Json
     */
    public function rSuccess($msg = "")
    {
        $return = "操作成功";
        if ($msg) $return = $return . ":" . arr2json($msg);
        return returnState(200,$return);
    }

    /**
     * 返回验证结果
     * @param $check
     * @return array|\think\response\Json
     */
    public function rValidate($check)
    {
        return returnValidate($check);
    }

    /**
     * 检查操作间隔
     * @param $session
     * @param int $second
     * @return array|bool|string
     */
    public function checkFrequency($session = '',$second = 2)
    {
        if (!$session) {
            $this->getController($controller);
            $action = request()->action();
            $session = $controller . ucwords($action);
        }
        $data = checkFrequency($session,$second);
        return $data;
    }

    /**
     * 获取控制器名称，一级直接用，两级格式按.分开成数组，拿第2个元素值
     * @param $controller
     */
    public function getController(&$controller)
    {
        $controller = request()->controller();
        if (strpos($controller,".") !== false) {
            $controller = explode(".",$controller)[1];
        }
    }
}