<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/5
 * Time: 12:00
 */
namespace app\AppFactory\Kernel\Model\WeiCheng;

use app\AppFactory\Kernel\Model\BaseModel;

class WcUserAddressesModel extends BaseModel
{
    /**
     * Primary key for the wc_users_addresses table
     *
     * @var string
     */
    protected $pk = "id";

    /**
     * Table name
     *
     * @var string
     */
    protected $name = "wc_users_addresses";

}