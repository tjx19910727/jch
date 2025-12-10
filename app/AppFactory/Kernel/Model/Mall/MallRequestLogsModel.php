<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 12:00
 */

namespace app\AppFactory\Kernel\Model\Mall;

use app\AppFactory\Kernel\Model\BaseModel;

class MallRequestLogsModel extends BaseModel
{
    /**
     * Primary key for the mall_machine table
     *
     * @var string
     */
    protected $pk = "id";

    /**
     * Table name
     *
     * @var string
     */
    protected $name = "mall_request_logs";
}