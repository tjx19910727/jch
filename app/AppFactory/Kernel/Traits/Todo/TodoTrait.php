<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/9/8
 * Time: 17:34
 */

namespace app\AppFactory\Kernel\Traits\Todo;


use app\AppFactory\Kernel\Model\Todo\TodoModel;

trait TodoTrait
{
    /**
     * 获取待办事项列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return TodoModel|TodoModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getTodoList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return TodoModel::getList($where,$pageNum,$field,$order);
    }

    // 获取待办事项详情
    public function getTodoDetails()
    {

    }

    public function addTodo($insert)
    {
        $data = TodoModel::create($insert);
        return $data->id;
    }

    public function updateTodo($update,$where = [],$field = [])
    {
        return TodoModel::update($update,$where,$field);
    }
}