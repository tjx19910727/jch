<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/29
 * Time: 12:00
 */
namespace app\AppFactory\Kernel\Model\WeiCheng;

use app\AppFactory\Kernel\Model\BaseModel;

class WcMachineChannelModel extends BaseModel
{
    /**
     * Primary key for the mall table
     *
     * @var string
     */
    protected $pk = "mc_id";

    /**
     * Table name
     * @var string
     */
    protected $name = "wc_machine_channel";

}