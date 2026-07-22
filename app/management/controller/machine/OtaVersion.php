<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/23
 * Time: 10:00
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VOtaVersion;

class OtaVersion extends Common
{

    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["version_no" => "like"]);
        return $this->app->otaVersion->getList($where, $pageNum, $this->field, 'ov_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->otaVersion->getFind($where, $this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, VOtaVersion::class . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->otaVersion->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, VOtaVersion::class . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->otaVersion->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, VOtaVersion::class . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->otaVersion->del($postData);
    }
}
