<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:45
 */

namespace app\management\controller\goods;


use app\management\controller\Common;

class Goods extends Common
{
    protected $field = "g_id,g_name,gc_id,gc_name,g_type,`model`,bar_code,`sku`,`sku2`,
    banner,pic,cost_price,market_price,retail_price,manufacturer,service_phone,performance,sell_channel,expire_notice,
    is_gift,is_recommend,recoverable,heat,release_time,length,width,height,group_quantity,status,ao_id,creator,create_time,update_time";
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
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,['g_name' => "like",'sku' => "like"]);
        $result = $this->app->goods->getList($where,$pageNum,$this->field,'g_id desc');
        return $result;
    }

    /**
     * 添加商品
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
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
        try { $this->validate($postData,$this->validatePath . 'update');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->goods->update($postData);
        return $result;
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
        return $this->app->goods->importExcel($postData);
    }

    /**
     * 导出商品Excel
     * @return array|string
     */
    public function exportExcel()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["g_id" => "in","g_name" => "like","gc_name" => "like","sku" => "like","manufacturer" => "like"]);
        return $this->app->goods->exportExcel($where);
    }

}