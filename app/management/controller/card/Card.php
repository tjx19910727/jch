<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/09
 * Time: 08:50
 */

namespace app\management\controller\card;

use app\AppFactory\AppFactory;
use app\management\controller\Common;
use app\management\validate\Card\VCard;

class Card extends Common
{

    protected $field = "*";
    protected $validatePath = VCard::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['card_no' => 'like']);
        return $this->app->card->getCardInfoList($where, $pageNum, "*", 'card_no desc');
    }


    public function changePoints()
    {
        try {
            $this->validate(input(), $this->validatePath . '.add');

        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->card->changeCardPoints(input('card_no'), input('points_changed'), input('change_type'), input('oredr_id') ?? '', input('reason') ?? '', input('bind_id') ?? '');
    }

}