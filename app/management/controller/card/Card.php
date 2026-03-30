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
        if(isset($where['ao_id'])) unset($where['ao_id']);
        return $this->app->card->getCardInfoList($where, $pageNum, "*", 'card_no desc');
    }

    //新增单条卡信息
    public function add()
    {
        $postData = input();
        $this->validate($postData, $this->validatePath . '.add_2');
        return returnData($this->app->card->addSingleCard($postData));
    }


    public function changePoints()
    {
        try {
            $this->validate(input(), $this->validatePath . '.add');
            return returnData($this->app->card->changeCardPoints(input('card_no'), input('points_changed'), input('change_type'), input('trade_no') ?? '', input('reasons') ?? '', input('bind_id') ?? ''));
        } catch (\Exception $e) {
            return $this->app->card->rFail('积分变动失败');
        }
    }

    //导入卡信息
    public function import(){
        return $this->app->card->importCards(input());
    }

    //导出卡信息
    public function export()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['card_no' => 'like', 'card_show_no' => 'like', 'name' => 'like']);
        if(isset($where['ao_id'])) unset($where['ao_id']);
        return $this->app->card->exportCards($where);
    }

    //充值
    public function changeBalance()
    {
        try {
            $this->validate(input(), $this->validatePath . '.addBalance');
            $postData = input();
            $pwd = $postData['pwd'] ?? '';
            if (!$pwd){
                return returnState(100,lang("VLogin.password_require"));
            }
            if (md5($pwd.config("app.salt")) !=  $this->manager['password']){
                return returnState(100,lang("VLogin.pwd_incorrect"));
            }
            unset($postData['pwd']);
            $res = $this->app->card->changeCardBalance($postData);
            return returnData($res);
        } catch (\Exception $e) {
            return $this->app->card->rFail(lang("VCard.balance_action_fail") .'：'. $e->getMessage());
        }
    }

    //改密
    public function changePwd()
    {
        try {
            $this->validate(input(), $this->validatePath . '.changePwd');
            $postData = input();
            $res = $this->app->card->changeCardPwd($postData);
            return returnData($res);
        } catch (\Exception $e) {
            return $this->app->card->rFail(lang("VCard.balance_action_fail") .'：'. $e->getMessage());
        }
    }

}