<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/17
 * Time: 20:02
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Traits\Goods\GoodsLangTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsLangClient extends ManagementClient
{
    use GoodsLangTrait;

    /**
     * 添加商品多语言数据
     * @param $postData
     * @return array|\think\response\Json
     */
    public function addGl($postData)
    {
        $gl = $this->getGoodsLangFind(['g_id' => $postData['g_id'],'lang' => $postData['lang']]);
        if ($gl) return $this->rFail($this->lang("VGoodsLang.is_exist"));
        $result = $this->addGoodsLang($postData);
        return $this->rA($result);
    }
}