<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/1/4
 * Time: 10:50
 */

namespace AliPay\trade\royalty;


use AliPay\Kernel\BaseClient;

class RelationClient extends BaseClient
{

    /**
     * 外部请求号，由商家自定义
     */
    public function getOutRequestNo()
    {
        isset($this->bizContent['out_request_no']) && !$this->bizContent['out_request_no'] ? $this->bizContent['out_request_no'] = date("YmdHis").get_rand_string(6) : [];
    }

    /**
     * 分账关系绑定
     * @param $config
     * @param $receiverList
     * @return int
     */
    public function bind($receiverList)
    {
        $this->onceName = "AlipayTradeRoyaltyRelationBindRequest";
        $this->requireOnce();
        $this->bizContent["receiver_list"] = $receiverList;
        return $this->execute();
    }

    /**
     * 分账关系解绑
     * @param $config
     * @param $receiverList
     * @return int
     */
    public function unbind($receiverList)
    {
        $this->onceName = "AlipayTradeRoyaltyRelationUnbindRequest";
        $this->requireOnce();
        $this->bizContent['receiver_list'] = $receiverList;
        return $this->execute();
    }

    /**
     * 查询分账绑定表
     * @param $config
     * @param int $page
     * @param int $size
     */
    public function batchQuery($page = 1,$size = 20)
    {
        $this->onceName = "AlipayTradeRoyaltyRelationBatchqueryRequest";
        $this->requireOnce();
        $this->bizContent['page_num'] = $page;
        $this->bizContent['page_size'] = $size;
        return $this->execute();
    }
}