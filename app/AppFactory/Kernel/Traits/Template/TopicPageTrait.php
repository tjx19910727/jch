<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/10
 * Time: 11:41
 */

namespace app\AppFactory\Kernel\Traits\Template;


use app\AppFactory\Kernel\Model\Template\TopicPageModel;
use app\AppFactory\Kernel\Model\Template\TopicPageMachineModel;

trait TopicPageTrait
{
    public function getTopicPageFind($where, $field = "*", $order = "")
    {
        return TopicPageModel::getFind($where, $field, $order);
    }

    public function getTopicPageList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return TopicPageModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addTopicPage($insert)
    {
        if (!isset($insert['manager_id']) && isset($this->manager['manager_id'])) {
            $insert['manager_id'] = $this->manager['manager_id'];
        }
        $data = TopicPageModel::create($insert);
        return $data->id;
    }

    public function updateTopicPage($update, $where = [], $field = [])
    {
        return TopicPageModel::update($update, $where, $field);
    }

    public function delTopicPage($where)
    {
        return TopicPageModel::whereDel($where);
    }

    public function getTopicPageMachineList($where, $pageNum = 0, $field = "*", $order = "topic_id asc")
    {
        return TopicPageMachineModel::getList($where, $pageNum, $field, $order);
    }

    public function getTopicPageMachineColumn($where, $column)
    {
        return TopicPageMachineModel::getColumn($where, $column);
    }

    public function getTopicPageMachineCount($where)
    {
        return TopicPageMachineModel::getCount($where);
    }

    public function delTopicPageMachine($where)
    {
        return TopicPageMachineModel::whereDel($where);
    }

    public function addTopicPageMachineMore($insertAll)
    {
        if (!$insertAll) return true;
        return TopicPageMachineModel::insertAll($insertAll);
    }
}
