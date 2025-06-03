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

    public function getAuthNodeValue($where,$value)
    {
        return AuthNodeModel::getFieldValue($where,$value);
    }

    public function getAuthNodeColumn($where,$column)
    {
        return AuthNodeModel::getColumn($where,$column);
    }


    public function getAuthNodeFind($where, $field = "*", $order = "")
    {
        return AuthNodeModel::getFind($where, $field, $order);
    }

    public function getAuthNodeList($where, $pageNum = 0, $field = "*", $order = "",$group = "")
    {
        return AuthNodeModel::getList($where, $pageNum, $field, $order,'',$group);
    }

    /**
     * 获取指定节点上级树
     * @param $id
     * @param string $field
     * @return array
     */
    public function getAuthNodeFatherList($id,$field = "*")
    {
        $data = [];
        if ($id > 0) {
            $father = $this->getAuthNodeFind(['node_id' => $id], $field);
            if ($father) {
                $father = $father->toArray();
                $data[] = $father;
                if (isset($father['pid']) && $father['pid'] > 0) {
                    $data = array_merge($data,$this->getAuthNodeFatherList($father['pid'],$field));
                }
            }
        }
        return $data;
    }

    /**
     * 递归获取所有子节点列表
     * @param int $pid
     * @return array
     */
    public function getAuthNodeChildIdList($pid)
    {
        $id = $this->getAuthNodeColumn(['pid' => $pid],'node_id');
        if ($id) {
            foreach ($id as $i) {
                $child = $this->getAuthNodeChildIdList($i);
                if ($child) {
                    $id = array_merge($id,$child);
                }
            }
        }
        return $id ? $id : [];
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

            // 门票核销列表
            "/management/sale.sale_orders/queryTicket" => "canceller",

            // 策略管理
            "/management/strategy.strategy_agreement/getList" => "creator",
            "/management/strategy.strategy_hosting/getList" => "creator",
            "/management/strategy.strategy_income/getList" => "creator",
            "/management/strategy.strategy_manager/getList" => "creator",
            "/management/strategy.strategy_payee/getList" => "creator",

            // 会员管理
            "/management/user.user/getList" => "creator",

            // 首页数据
            "/management/index/getMachineData" => "creator",
            "/management/index/getChannelData" => "creator",
            "/management/index/getGift" => "manager_id",
            "/management/index/getMachine10List" => "creator",
            "/management/index/getGoods10List" => "creator",
        ];
        return isset($urlAuthDataField[$url]) ? $urlAuthDataField[$url] : [];
    }
}