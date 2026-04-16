<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/10
 * Time: 11:40
 */

namespace app\management\controller\template;


use app\management\controller\Common;
use app\management\validate\VTopicPage;

class TopicPage extends Common
{
    protected $field = "*";
    protected $validatePath = VTopicPage::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->topicPage->getTopicList($where, $pageNum, $this->field, 'id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->topicPage->getTopicFindData($where, $this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $postData['status'] = $postData['status'] ?? 0;
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->topicPage->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->topicPage->updateTopic($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->topicPage->delTopic($postData);
    }

    public function assignMachine()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'assignMachine');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->topicPage->assignMachine($postData);
    }

    public function setStatus()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'setStatus');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->topicPage->setStatus($postData);
    }

    public function copy()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'copy');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->topicPage->copyTopic($postData);
    }
}
