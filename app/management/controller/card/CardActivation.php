<?php
/**
 * 卡激活活动管理
 */

namespace app\management\controller\card;

use app\management\controller\Common;
use app\management\validate\Card\VCardActivation;

class CardActivation extends Common
{
    protected $field = '*';
    protected $validatePath = VCardActivation::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['pick_name' => 'like']);
        if (isset($where['ao_id'])) unset($where['ao_id']);
        return $this->app->cardActivation->getCardActivationInfoList($where, $pageNum, $this->field, 'id desc');
    }

    public function getFind()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.getFind');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->cardActivation->getCardActivationInfoFind(['id' => $postData['id']]);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->cardActivation->addCardActivationInfo($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->cardActivation->updateCardActivationInfo($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->cardActivation->delCardActivationInfo($postData['id']);
    }

    public function getDetailList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.getDetailList');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->cardActivation->getCardActivationDetailInfoList(['aca_id' => $postData['id']], $pageNum, '*', 'acd_id desc');
    }

    public function delDetail()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.delDetail');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->cardActivation->delCardActivationDetailInfo($postData['acd_id']);
    }

    public function import()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.import');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->cardActivation->importCardActivationDetailInfo($postData);
    }

    public function getSelectableCardList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->cardActivation->getSelectableUnactivatedCards($postData, $pageNum);
    }
}
