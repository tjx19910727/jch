<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/09
 * Time: 08:50
 */

namespace app\management\controller\mall;

use app\AppFactory\AppFactory;
use app\management\controller\Common;
use app\management\validate\Mall\VMall;

class Mall extends Common
{

    protected $field = "*";
    protected $validatePath = VMall::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['m.mall_name' => 'like']);
        return $this->app->mall->getMallInfoList($where, $pageNum, "m.*", 'm.mall_id desc');
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');

        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->mall->addMallInfo($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $where['mall_id'] = $postData['mall_id'];
        unSet($postData['mall_id']);
        return $this->app->mall->updateMallInfo($postData, $where);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->mall->delMallInfo($postData);
    }
}