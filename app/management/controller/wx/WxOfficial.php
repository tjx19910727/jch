<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 9:27
 */

namespace app\management\controller\wx;


use app\management\controller\Common;

class WxOfficial extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\Wx\VWxOfficial.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->wxOfficial->getList($where,$pageNum,$this->field,'id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->wxOfficial->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->wxOfficial->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->wxOfficial->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->wxOfficial->del($postData);
    }

    /**
     * 获取公众号二维码
     * @return array|string
     */
    public function getQrCode()
    {
        $postData = input();
        return $this->app->wxOfficial->getQrCode($postData);
    }
}