<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 11:18
 */

namespace Jd\Declares\Attach;


use Jd\Kernel\BaseClient;

class AttachClient extends BaseClient
{

    /**
     * 附件上传
     * 注：
        1、上传图片需要Base64编码 。
     * @param $params
     * @return bool|string
     */
    public function upload($params)
    {
        $url = '/v2/agent/declare/attach/upload';
        return $this->httpPost($url,$params);
    }

    /**
     * 附件修改
     * @param $params
     * @return bool|string
     */
    public function modify($params)
    {
        $url = '/v2/agent/declare/attach/modify';
        return $this->httpPost($url,$params);
    }

    /**
     * 附件查询
     * @param $customerNum
     * @return bool|string
     */
    public function query($customerNum)
    {
        $url = "/v1/agent/declare/attach/list/$customerNum";
        return $this->httpGet($url);
    }
}