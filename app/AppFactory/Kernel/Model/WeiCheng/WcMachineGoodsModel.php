<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/29
 * Time: 12:00
 */
namespace app\AppFactory\Kernel\Model\WeiCheng;

use app\AppFactory\Kernel\Model\BaseModel;

class WcMachineGoodsModel extends BaseModel
{
    /**
     * Primary key for the wc_machine_goods table
     *
     * @var string
     */
    protected $pk = "id";

    /**
     * Table name
     * @var string
     */
    protected $name = "wc_machine_goods";

}