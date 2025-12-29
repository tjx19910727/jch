<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/09
 * Time: 08:50
 */

namespace app\management\controller\mall;


use app\management\controller\Common;
use app\management\validate\Mall\VMallMachine;

class MallMachine extends Common
{

    protected $field = "*";
    protected $validatePath = VMallMachine::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        $where['status'] = 2;
        return $this->app->mallMachine->getMallMachineInfoList($where, $pageNum, $this->field, 'id desc');
    }

    public function bind()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.bind');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->mallMachine->bind($postData['mall_id'],$postData['m_ids']);
    }

    // public function add()
    // {
    //     $postData = input();
    //     try {
    //         $this->validate($postData, $this->validatePath . '.add');
    //     } catch (\Exception $e) {
    //         return returnValidate($e->getMessage());
    //     }
    //     return $this->app->mallMachine->addMall($postData);                                                                                                                                                                                                                                                                                                                                                                                                                                                    
    // }

    // public function update()
    // {
    //     $postData = input();
    //     $where['mall_id'] = $postData['mall_id'];
    //     unSet($postData['mall_id']);
    //     try {
    //         $this->validate($postData, $this->validatePath . '.update');
    //     } catch (\Exception $e) {
    //         return returnValidate($e->getMessage());
    //     }
    //     return $this->app->mallMachine->updateMall($postData, $where);
    // }

    // public function del()
    // {
    //     $postData = input();
    //     try {
    //         $this->validate($postData, $this->validatePath . '.del');
    //     } catch (\Exception $e) {
    //         return returnValidate($e->getMessage());
    //     }
    //     return $this->app->mallMachine->delMall($postData);
    // }
}