<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:53
 */

namespace app\management\controller\strategy;


use app\management\controller\Common;

class StrategyPayee extends Common
{
    protected $validatePath = 'app\management\validate\VStrategyPayee.';

    protected function checkContent($postData)
    {
        $checkName = "";
        $checkData = json2arr($postData['content']);
        switch ($postData['payee_type']) {
            case 1: $checkName = "addWx";  break;
            case 2: $checkName = "addAli";  break;
            case 3: $checkName = "addTl";  break;
            case 4: $checkName = "addJdCashier";  break;
            case 5: $checkName = "addTrip";  break;
        }
        if (!$checkName) return returnValidate("未定义收款策略名称");
        $check = $this->validate($checkData,$this->validatePath . $checkName);
        if ($check !== true) return returnValidate($check);
        return true;
    }

    /**
     * 添加收款方策略
     * @return array|bool|mixed|string
     */
    public function add()
    {
        $postData = input();

        try { $this->validate($postData,$this->validatePath .'addSp');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $checkContent = $this->checkContent($postData);
        if ($checkContent !== true) return $checkContent;
        //这里不知道是谁添加的策略，但是存到数据库中的ao_id一定得是这个人的顶级组织id
        if (!isset($postData['ao_id'])) $postData['ao_id'] = $this->getOriginAoId($this->manager['ao_id']);
        return $this->app->strategyPayee->add($postData);
    }

    /**
     * 修改收款方策略
     * @return array|bool|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath .'updateSp');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        if (isset($postData['content'])) {
            $checkContent = $this->checkContent($postData);
            if ($checkContent !== true) return $checkContent;
        }
        try {
            return $this->app->strategyPayee->update($postData);
        } catch (\Exception $e) {
            actionException($e,1);
            return returnTryCatch($e->getMessage());
        }
    }

    /**
     * 查询收款方策略列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['app_id' => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        $field = "sp_id,sp_name,title,payee_type,app_id,mch_id,content,ico,status,create_time,update_time";
        return $this->app->strategyPayee->getList($where,$pageNum,$field,"sp_id desc");
    }

    /**
     * 查询收款方一条策略信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->strategyPayee->getFind($where,"*","sp_id desc");
    }

    /**
     * 删除一条策略
     * @return array|mixed|string
     */
    public function del()
    {
        $id = input('sp_id');
        if (!$id) return returnState(100,'策略ID不能为空');
        return $this->app->strategyPayee->del($id);
    }


    /**
     * 2-3. 获取平台证书
     * @return array|string
     */
    public function getPlatformCert()
    {
        $sp_id = input('sp_id');
        return $this->app->strategyPayee->getWxPlatformCert($sp_id);
    }
}