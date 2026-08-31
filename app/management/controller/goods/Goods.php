<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:45
 */

namespace app\management\controller\goods;


use app\management\controller\Common;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;

class Goods extends Common
{
    protected $field = "g_id,g_name,gc_id,gc_name,g_type,`model`,bar_code,`sku`,`sku2`,
    banner,pic,cost_price,market_price,retail_price,intergral_rate,manufacturer,service_phone,performance,sell_channel,exter_url,expire_notice,sell_by_date,
    is_gift,is_recommend,recoverable,heat,release_time,length,width,height,group_quantity,stocks,locked_stocks,
    (stocks-locked_stocks) available_stocks,status,ao_id,creator,create_time,update_time";
    protected $validatePath = 'app\management\validate\VGoods.';

    /**
     * 查询一条商品列表
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $this->field .= ",`desc`,details_pic";
        $result = $this->app->goods->getFind($where,$this->field);
        return $result;
    }

    /**
     * 获取商品列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $field = $this->getFieldWithCostPriceAuth($this->field, $hasCostPriceAuth);
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,['g_name' => "like",'sku' => "like"]);
        if (isset($postData['bar_code'])) {
            if ((string)$postData['bar_code'] == 1) {
                $where[] = ['bar_code','not like','69%'];
            } elseif ((string)$postData['bar_code'] == 2) {
                $where[] = ['bar_code','like','69%'];
            }
        }
        if(!empty($postData['machine_id'])||!empty($postData['sale_check'])){
            $result = $this->app->goods->getAuthList($where,$pageNum,$field,'g_id desc',$postData);
            return $result;
        }
        $result = $this->app->goods->getList($where,$pageNum,$field,'g_id desc');
        return $result;
    }

    /**
     * 获取设备商品库商品列表
     * 传入 m_id（如：11,22,33）时仅返回这些设备商品库中的商品；
     * 可传入 gc_id 按商品分类筛选；m_id 未传或为空时返回全部商品。
     * @return mixed
     */
    public function getMcList()
    {
        $postData = input();
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $field = $this->getFieldWithCostPriceAuth($this->field, $hasCostPriceAuth);
        $pageNum = $postData['pageNum'] ?? 0;

        $mIds = $postData['m_id'] ?? [];
        unset($postData['m_id']);
        $where = $this->getWhere($postData,false,['g_name' => "like",'sku' => "like"]);
        if (!is_array($mIds)) {
            $mIds = explode(",",$mIds);
        }
        $mIds = array_values(array_filter(array_map('trim',$mIds), function ($v) {
            return $v !== '';
        }));

        if ($mIds) {
            $whereMg[] = ['m_id','in',$mIds];
            $gIds = $this->app->machineGoods->getMachineGoodsColumn($whereMg,'g_id');
            $gIds = array_values(array_unique(array_filter($gIds)));
            if (!$gIds) {
                $where[] = ['g_id','=',0];
            } else {
                $where[] = ['g_id','in',$gIds];
            }
        }

        return $this->app->goods->getList($where,$pageNum,$field,'g_id desc');
    }

    /**
     * 按商品维度统计在营设备上架、货道库存与周期销量
     * @return mixed
     */
    public function getOperatingGoodsList()
    {
        $postData = input();
        return $this->app->goods->getOperatingGoodsList($postData);
    }

    /**
     * 导出商品维度在营设备上架、货道库存与周期销量
     * @return mixed
     */
    public function exportOperatingGoodsList()
    {
        $postData = input();
        return $this->app->goods->exportOperatingGoodsList($postData);
    }

    /**
     * 添加商品
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        unset($postData['stocks'], $postData['locked_stocks'], $postData['available_stocks']);
        try { $this->validate($postData,$this->validatePath . 'add');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->goods->addG($postData);
        return $result;
    }

    /**
     * 修改商品
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        //'商品库存不允许通过商品编辑接口修改'
        if (array_key_exists('stocks', $postData)) unset($postData['stocks']);
        if (array_key_exists('locked_stocks', $postData)) unset($postData['locked_stocks']);
        if (array_key_exists('available_stocks', $postData)) unset($postData['available_stocks']);
        try { $this->validate($postData,$this->validatePath . 'update');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->goods->updateForEdit($postData);
        //$result = $this->app->goods->update($postData);
        return $result;
    }

    /**
     * 获取价格差异的设备商品和货道列表
     * @return mixed
     */
    public function getPriceDiff()
    {
        $postData = input();
        return $this->app->goods->getPriceDiff($postData);
    }

    /**
     * 删除商品
     * @return mixed
     */
    public function del()
    {
        $g_id = input("g_id");
        if (strpos($g_id,",")) $where[] = ['g_id',"in",$g_id];
        else $where['g_id'] = $g_id;
        $mc = $this->app->machineChannel->getMachineChannelFind($where,'mc_id,machine_id','');
        if ($mc) {
            $mc = $mc->toArray();
            $machine_id = implode(",",array_column($mc,"machine_id"));
            return returnState(100,lang("del_fail") . ":" . lang("VGoods.g_is_up") . "," . $machine_id);
        }
        $result = $this->app->goods->del($where);
        return $result;
    }

    /**
     * 导入商品Excel
     * @return array|string
     */
    public function importExcel()
    {
        $postData = input();
        return $this->app->goods->importExcelV2($postData);
    }

    /**
     * 导出商品Excel
     * @return array|string
     */
    public function exportExcel()
    {
        $postData = input();
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $where = $this->getWhere($postData,false,["g_id" => "in","g_name" => "like","gc_name" => "like","sku" => "like","manufacturer" => "like"]);
        if (isset($postData['bar_code'])) {
            if ((string)$postData['bar_code'] == 1) {
                $where[] = ['bar_code','not like','69%'];
            } elseif ((string)$postData['bar_code'] == 2) {
                $where[] = ['bar_code','like','69%'];
            }
        }
        return $this->app->goods->exportExcel($where, $hasCostPriceAuth);
    }

    /**
     * 导出所有商品Excel
     * @return array|string
     */
    public function exportAllGoodsToExcel()
    {
        $postData = input();
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $where = $this->getWhere($postData,false,["g_id" => "in","g_name" => "like","gc_name" => "like","sku" => "like","manufacturer" => "like"]);
        return $this->app->goods->exportAllGoodsToExcel($where, $hasCostPriceAuth);
    }

    
    /**
     * 导出异常条形码商品Excel
     * @return array|string
     */
    public function exportAbnormalBarCode()
    {
        $postData = input();
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $where = $this->getWhere($postData,false,["g_id" => "in","g_name" => "like","gc_name" => "like","sku" => "like","manufacturer" => "like"]);
        $where[] = ['bar_code','not like','69%'];
        return $this->app->goods->exportAbnormalBarCodeExcel($where, $hasCostPriceAuth);
    }

    /**
     * 导入商品条形码
     * @return array|string
     */
    public function importBarCode()
    {
        $postData = input();
        return $this->app->goods->importBarCode($postData);
    }

}
