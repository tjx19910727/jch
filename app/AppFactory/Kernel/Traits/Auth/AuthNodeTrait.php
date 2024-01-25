<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:23
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthNodeModel;

trait AuthNodeTrait
{


    public function getAuthNodeFind($where, $field = "*", $order = "")
    {
        return AuthNodeModel::getFind($where, $field, $order);
    }

    public function getAuthNodeList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return AuthNodeModel::getList($where, $pageNum, $field, $order);
    }

    public function addAuthNode($insert)
    {
        $data = AuthNodeModel::create($insert);
        return $data->node_id;
    }

    public function updateAuthNode($update, $where = [], $field = [])
    {
        return AuthNodeModel::update($update, $where, $field);
    }

    public function delAuthNode($where)
    {
        return AuthNodeModel::whereDel($where);
    }


    public function getAuthDataFieldByUrl($url)
    {
        $urlAuthDataField = [
            // 账号及权限管理
            "/management/auth.auth_manager/getList" => "manager_id",
            "/management/auth.auth_role/getList" => "creator",

            // 配置管理
            "/management/config.config/getList" => "creator",

            // 商品管理
            "/management/goods.goods/getList" => "creator",
            "/management/goods.goods_category/getList" => "creator",

            // 微信开放平台管理
            "/management/openPlatform.open_platform_wx/getList" => "creator",

            // 订单管理
            "/management/sale.sale_orders/getList" => "store_manager",
            "/management/sale.sale_orders_refund/getList" => "creator",
            "/management/sale.sale_orders_revenue/getList" => "beneficiary",

            // 门店管理
            "/management/store.store/getList" => "store_manager",

            // 策略管理
            "/management/strategy.strategy_agreement/getList" => "creator",
            "/management/strategy.strategy_charge/getList" => "creator",
            "/management/strategy.strategy_hosting/getList" => "creator",
            "/management/strategy.strategy_income/getList" => "creator",
            "/management/strategy.strategy_manager/getList" => "creator",
            "/management/strategy.strategy_payee/getList" => "creator",

            // 会员管理
            "/management/user.user/getList" => "creator",

            // 库存管理
            "/management/warehouse.warehouse/getList" => "manager_id",
            "/management/warehouse.warehouse_check/getList" => "manager_id",
            "/management/warehouse.warehouse_order/getEnList" => "receiver",
            "/management/warehouse.warehouse_order/getOutList" => "sender",

            // 首页数据
            "/management/index/salesSummary" => "store_manager",
            "/management/index/profitSummary" => "store_manager",
            "/management/index/storeSaleList" => "store_manager",
            "/management/index/goodsSaleList" => "store_manager",
            "/management/index/unattendedSaleList" => "store_manager",
            "/management/index/cloudWhSaleList" => "store_manager",
            "/management/index/getBrokenLine" => "store_manager",
            "/management/index/storeSummary" => "store_manager",
            "/management/index/cargoDamageSummary" => "manager_id",
        ];
        return $urlAuthDataField[$url];
    }
}