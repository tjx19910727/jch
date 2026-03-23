<?php
/**
 * Created by lgf
 * User: AI Assistant
 * Date: 2026/03/19
 * Time: 15:00
 */

namespace app\AppFactory\Kernel\Model\Card;

use app\AppFactory\Kernel\Model\BaseModel;

class CardBalanceChangeLogsModel extends BaseModel
{
    /**
     * Primary key for the card_balance_change_logs table
     *
     * @var string
     */
    protected $pk = "id";

    /**
     * Table name
     *
     * @var string
     */
    protected $name = "card_balance_change_logs";
}
