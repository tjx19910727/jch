<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 12:00
 */

namespace app\AppFactory\Kernel\Model\Card;

use app\AppFactory\Kernel\Model\BaseModel;

class CardPointsChangeLogsModel extends BaseModel
{
    /**
     * Primary key for the card_points_change_logs table
     *
     * @var string
     */
    protected $pk = "id";

    /**
     * Table name
     *
     * @var string
     */
    protected $name = "card_points_change_logs";
}