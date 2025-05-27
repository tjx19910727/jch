<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/11/11
 * Time: 11:32
 */

namespace app\management\controller\action;


use app\management\controller\Common;
use app\management\validate\Action\VActionVideo;

class actionVideo extends Common
{
    
    protected $field = "*";
    protected $validatePath = VActionVideo::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["tag" => "like","video_name" => "like"]);
        return $this->app->actionVideo->getList($where, $pageNum, $this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->actionVideo->getFind($where, $this->field);
    }

    public function getTagList()
    {
        return $this->app->actionVideo->getTagList();
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->actionVideo->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->actionVideo->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->actionVideo->del($postData);
    }
}