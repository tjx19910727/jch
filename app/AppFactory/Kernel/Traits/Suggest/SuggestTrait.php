<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/17
 * Time: 9:57
 */

namespace app\AppFactory\Kernel\Traits\Suggest;



use app\AppFactory\Kernel\Model\Suggest\SuggestModel;

trait SuggestTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getSuggestValue($where, $value)
    {
        return SuggestModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getSuggestColumn($where, $column)
    {
        return SuggestModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getSuggestCount($where)
    {
        return SuggestModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getSuggestList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return SuggestModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getSuggestFind($where, $field = "*", $order = "")
    {
        return SuggestModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addSuggest($insert)
    {
        if (!isset($insert['creator']) || !$insert['creator']) $insert['creator'] = $this->manager['manager_id'];
        $data = SuggestModel::create($insert,['content','pic','email','creator','create_time']);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return SuggestModel
     */
    public function updateSuggest($update,$where = [],$field = [])
    {
        return SuggestModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delSuggest($where)
    {
        return SuggestModel::whereDel($where);
    }
}