<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:56
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryLangTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsCategoryClient extends ManagementClient
{
    use GoodsCategoryTrait,GoodsCategoryLangTrait;

    public function addGc($postData)
    {
        if (!isset($postData['ao_id'])) $postData['ao_id'] = $this->manager['ao_id'];
        $gc_id = $this->addGoodsCategory($postData);
        if ($gc_id) {
            $insertGl = [
                "gc_name" => $postData['gc_name'] ?? "",
                "desc" => $postData['desc'] ?? "",
                "lang" => "zh-cn",
                "gc_id" => $gc_id,
                "ao_id" => $this->manager['ao_id'],
            ];
            $this->addGoodsCategoryLang($insertGl);
        }
        return $this->rA($gc_id);
    }
}