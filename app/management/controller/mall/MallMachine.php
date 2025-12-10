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
use app\management\validate\Mall\VMallMachine;

class MallMachine extends Common
{

    protected $field = "*";
    protected $validatePath = VMallMachine::class;

    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->mallMachine->getMallMachineList($where, 10, $this->field, 'id desc');
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->mall->addMall($postData);
    }

    public function update()
    {
        $postData = input();
        $where['mall_id'] = $postData['mall_id'];
        unSet($postData['mall_id']);
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->mall->updateMall($postData, $where);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->mall->delMall($postData);
    }
}