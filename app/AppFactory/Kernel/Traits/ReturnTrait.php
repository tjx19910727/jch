<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:54
 */

namespace app\AppFactory\Kernel\Traits;


trait ReturnTrait
{
    public function r($state,$msg = "",$data = [],$isJson = true)
    {
        return returnState($state,$msg,$data,$isJson);
    }

    /**
     * 查询返回结果
     * @param $data
     * @return array|string
     */
    public function rQ($data)
    {
        return returnData($data, "查询成功|查无数据");
    }

    /**
     * 添加返回结果
     * @param $data
     * @return array|string
     */
    public function rA($data)
    {
        return returnData($data, "添加成功|添加失败");
    }

    /**
     * 修改返回结果
     * @param $data
     * @return array|string
     */
    public function rU($data)
    {
        return returnData($data, "修改成功|修改失败");
    }

    /**
     * 删除返回结果
     * @param $data
     * @return array|string
     */
    public function rD($data)
    {
        return returnData($data, "删除成功|删除失败");
    }

    /**
     * 操作返回结果
     * @param $data
     * @return array|string
     */
    public function rAction($data)
    {
        return returnData($data, "操作成功|操作失败");
    }

    /**
     * 复制返回结果
     * @param $data
     * @return array|string
     */
    public function rCopy($data)
    {
        return returnData($data, '复制成功|复制失败');
    }

    /**
     * 查无数据
     * @return array|string
     */
    public function rNoData()
    {
        return returnState(100, '查无数据');
    }

    /**
     * 返回失败
     * @param string $msg
     * @return array|string
     */
    public function rFail($msg = "")
    {
        $return = "操作失败";
        if ($msg) $return = $return . ":" . arr2json($msg);
        return returnState(100,$return);
    }

    /**
     * 返回成功
     * @param string $msg
     * @return array|string
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
     * @return array|string
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