<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/9/8
 * Time: 17:32
 */

namespace app\AppFactory\Management\Index;


use app\AppFactory\Kernel\Traits\Todo\TodoTrait;
use app\AppFactory\Management\ManagementClient;

class TodoClient extends ManagementClient
{
    use TodoTrait;

    /**
     * 获取待办事项列表
     * @param int $pageNum
     * @return array|mixed|string
     * @throws \Exception
     */
    public function getFieldList($pageNum = 0)
    {
        $where['status'] = 1;
        $where['manager_id'] = $this->manager['manager_id'];
        $field = "id,thing_id,type,title,content,status,create_time";
        return $this->rQ($this->getTodoList($where,$pageNum,$field,"id desc"));
    }

    /**
     * 忽略待办事项
     * @param $id
     * @return array|string
     */
    public function updateStatus($id)
    {
        $update['id'] = $id;
        $update['status'] = 2;
        return $this->rU($this->updateTodo($update));
    }
}