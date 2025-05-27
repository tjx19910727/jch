<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 9:13
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryLangTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsCategoryLangClient extends ManagementClient
{
    use GoodsCategoryLangTrait;
}