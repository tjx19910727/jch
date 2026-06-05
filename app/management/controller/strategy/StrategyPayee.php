<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:53
 */

namespace app\management\controller\strategy;


use app\management\controller\Common;
use think\facade\Db;

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
            case 9: $checkName = "addShopPoints";  break;
            case 20: $checkName = "addBalance";  break;
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
        if (!isset($postData['ao_id'])) $postData['ao_id'] = $this->manager['ao_id'];
        $revenueConfig = $this->pickRevenueConfig($postData);
        Db::startTrans();
        try {
            $spId = $this->app->strategyPayee->add($postData, 0);
            if ($spId && $revenueConfig) {
                $revenueConfig['sp_id'] = $spId;
                $saveConfig = $this->app->revenuePayeeConfig->saveByPayee($revenueConfig);
                if (isset($saveConfig['code']) && intval($saveConfig['code']) !== 200) {
                    Db::rollback();
                    return $saveConfig;
                }
            }
            Db::commit();
            return $this->app->strategyPayee->rA($spId);
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e,1);
            return returnTryCatch($e->getMessage());
        }
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
        $revenueConfig = $this->pickRevenueConfig($postData);
        Db::startTrans();
        try {
            $result = $this->app->strategyPayee->update($postData, [], [], 0);
            if ($result && $revenueConfig) {
                $revenueConfig['sp_id'] = $postData['sp_id'];
                $saveConfig = $this->app->revenuePayeeConfig->saveByPayee($revenueConfig);
                if (isset($saveConfig['code']) && intval($saveConfig['code']) !== 200) {
                    Db::rollback();
                    return $saveConfig;
                }
            }
            Db::commit();
            return $this->app->strategyPayee->rU($result);
        } catch (\Exception $e) {
            Db::rollback();
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
        $field = "sp_id,sp_name,title,payee_type,app_id,mch_id,content,ico,status,ao_id,create_time,update_time";
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

    /**
     * 导出收款策略
     * @return array|string
     */
    public function exportPayee()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['app_id' => "like"]);
        return $this->app->strategyPayee->exportPayee($where);
    }

    protected function pickRevenueConfig(&$postData)
    {
        $keys = ['default_ra_id', 'default_manager_id', 'enable_revenue'];
        $hasConfig = false;
        $config = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $postData)) {
                $hasConfig = true;
                $config[$key] = $postData[$key];
                unset($postData[$key]);
            }
        }
        if (!$hasConfig) return [];
        if (isset($postData['payee_type'])) $config['payee_type'] = $postData['payee_type'];
        if (isset($postData['ao_id'])) $config['ao_id'] = $postData['ao_id'];
        if (!isset($config['enable_revenue'])) $config['enable_revenue'] = 1;
        return $config;
    }

    public function saveRevenueConfig()
    {
        $postData = input();
        if (empty($postData['sp_id'])) return returnState(100, '收款策略ID不能为空');
        return $this->app->revenuePayeeConfig->saveByPayee($postData);
    }

    public function getRevenueConfig()
    {
        $spId = input('sp_id');
        if (!$spId) return returnState(100, '收款策略ID不能为空');
        return $this->app->revenuePayeeConfig->getFind(['sp_id' => $spId]);
    }
}
