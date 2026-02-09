<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 12:00
 */
namespace app\AppFactory\Kernel\Model\Card;

use app\AppFactory\Kernel\Model\BaseModel;

class CardModel extends BaseModel
{
    /**
     * Primary key for the card table
     *
     * @var string
     */
    protected $pk = "card_no";

    /**
     * Table name
     *
     * @var string
     */
    protected $name = "card";

    
}