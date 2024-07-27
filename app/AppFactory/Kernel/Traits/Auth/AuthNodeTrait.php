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

            // 订单管理
            "/management/sale.sale_orders/getList" => "manager_id",
            "/management/sale.sale_orders_refund/getList" => "creator",
            "/management/sale.sale_orders_revenue/getList" => "beneficiary",

            // 策略管理
            "/management/strategy.strategy_agreement/getList" => "creator",
            "/management/strategy.strategy_hosting/getList" => "creator",
            "/management/strategy.strategy_income/getList" => "creator",
            "/management/strategy.strategy_manager/getList" => "creator",
            "/management/strategy.strategy_payee/getList" => "creator",

            // 会员管理
            "/management/user.user/getList" => "creator",

            // 首页数据
        ];
        return isset($urlAuthDataField[$url]) ? $urlAuthDataField[$url] : [];
    }
}