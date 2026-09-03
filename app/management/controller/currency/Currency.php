<?php

namespace app\management\controller\currency;

use app\management\controller\Common;
use app\management\validate\Currency\VCurrency;

class Currency extends Common
{
    public function getEnabledList()
    {
        return $this->app->currency->getEnabledList();
    }

    public function getList()
    {
        return $this->app->currency->getCurrencyList(input());
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, VCurrency::class . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->currency->addCurrency($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, VCurrency::class . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->currency->updateCurrency($postData);
    }

    public function getReferences()
    {
        return $this->app->currency->getReferences(input('currency_code'));
    }
}
