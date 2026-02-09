<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/5
 * Time: 12:00
 */
namespace app\AppFactory\Kernel\Model\WeiCheng;

use app\AppFactory\Kernel\Model\BaseModel;

class WcGoodsLocalModel extends BaseModel
{
    /**
     * Primary key for the mall table
     *
     * @var string
     */
    protected $pk = "id";

    /**
     * Table name
     *
     * @var string
     */
    protected $name = "wc_goods_local";

}