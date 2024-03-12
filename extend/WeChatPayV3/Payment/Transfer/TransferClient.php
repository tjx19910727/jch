<?php


namespace WeChatPayV3\Payment\Transfer;


use WeChatPayV3\Kernel\Support\Rsa;
use WeChatPayV3\Kernel\BaseClient;

class TransferClient extends BaseClient
{

    /**
     * 发起商家转账
     * @param array $params
     * @return bool|string
     */
    public function batches(array $params)
    {
        $publicKey = file_get_contents($this->app['config']->get('platform_path'));
        $unset = 1;
        foreach ($params['transfer_detail_list'] as $key => $value) {
            if ($value['transfer_amount'] >= 200000) {
                $unset = 0;
                $params['transfer_detail_list'][$key]['user_name'] = Rsa::encrypt($value['user_name'], $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
            } else {
                if ($unset) unset($params['transfer_detail_list'][$key]['user_name']);
            }
        }
        return $this->httpPost('/v3/transfer/batches', $params);
    }

    /**
     * 微信支付批次单号查询批次单
     * @param string $batch_id 微信批次单号，微信商家转账系统返回的唯一标识
     * @param int $limit 该次请求可返回的最大明细条数，最小20条，最大100条，不传则默认20条。不足20条按实际条数返回
     * @param string $detail_status 查询指定状态的转账明细单，当need_query_detail为true时，该字段必填，
     *                              ALL:全部。需要同时查询转账成功和转账失败的明细单
     *                              SUCCESS:转账成功。只查询转账成功的明细单
     *                              FAIL:转账失败。只查询转账失败的明细单
     * @param bool $detail 是否查询转账明细单，枚举值：true：是；false：否，默认否。
     * @param int $offset 请求资源起始位置，该次请求资源的起始位置，从0开始，默认值为0
     * @return bool|string
     */
    public function queryByBatchId($batch_id, $limit = 20, $detail_status = 'ALL', $detail = true, $offset = 0)
    {
        $params = [
            "need_query_detail=" . $detail,
            "offset=" . $offset,
            "limit=" . $limit,
            "detail_status=" . $detail_status,
        ];
        $params = implode('&', $params);
        return $this->httpGet('/v3/transfer/batches/batch-id/' . $batch_id . "?" . $params);
    }

    /**
     * 微信支付明细单号查询明细单
     * @param string $batch_id 微信批次单号，微信商家转账系统返回的唯一标识
     * @param string $detail_id 微信支付系统内部区分转账批次单下不同转账明细单的唯一标识
     * @return bool|string
     */
    public function queryByDetailId($batch_id = '', $detail_id = '')
    {
        return $this->httpGet('/v3/transfer/batches/batch-id/' . $batch_id . '/details/detail-id/' . $detail_id);
    }
}