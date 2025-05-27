<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/9
 * Time: 16:51
 */

namespace Jd\Api\Auth;


use Jd\Kernel\BaseClient;

class AuthClient extends BaseClient
{
    /**
     * 追加支付授权目录
     * 适用于服务商调用JSAPI接口，扫码跳转自己的H5页面，在调起支付时需要追加支付授权目录，支付授权目录追加成功，不能进行修改和删除。
     * 注意：
    1、请在提交前仔细核对授权目录地址格式是否正确，目前仅支持商户追加两条记录，支付目录不允许进行修改和删除。
    2、支付目录支持添加到根目录，如果多条支付目录，域名相同，只需添加域名即可。
    3、每个商户仅支持追加两条支付目录，务必核实之后进行追加。
     * @param array $params
     * @return bool|string
     */
    public function addAuthPayDirsDevConfig(array $params)
    {
        $url = '/api/addAuthPayDirsDevConfig';
        return $this->httpPost($url,$params);
    }

    /**
     * 支付授权目录结果查询
     * 该接口用于支付授权目录追加结果查询，判断是否追加成功。
     * @param array $params
     * @return bool|string
     */
    public function queryAuthPayDirsByBatchNum(array $params)
    {
        $url = '/api/queryAddAuthPayDirsDevConfigByBatchNum';
        return $this->httpPost($url,$params);
    }

}