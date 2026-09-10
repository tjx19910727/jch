<?php

namespace app\management\controller\machine;

use app\management\controller\Common;

class MachineLoadingScheme extends Common
{
    protected $validatePath = '';

    public function getGoodsList()
    {
        return $this->app->machineLoadingScheme->getGoodsList();
    }

    public function getDetail()
    {
        return $this->app->machineLoadingScheme->getDetail();
    }

    public function save()
    {
        return $this->app->machineLoadingScheme->save();
    }

    /**
     * 按货架导出货道模板方案
     * @return array|\think\response\Json
     */
    public function export()
    {
        $templateId = input('template_id');
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        return $this->app->machineLoadingScheme->exportByShelf($templateId, $hasCostPriceAuth);
    }

    /**
     * 按层级导出货道模板方案
     * @return array|\think\response\Json
     */
    public function exportByShelfLevel()
    {
        $templateId = input('template_id');
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        return $this->app->machineLoadingScheme->exportByShelfLevel($templateId, $hasCostPriceAuth);
    }
}

