<?php

namespace app\management\controller\fault_notice;

use app\management\controller\Common;

/**
 * 故障目录与分级后台接口。
 */
class FaultCatalog extends Common
{
    public function getCategoryList()
    {
        return $this->app->faultNotice->getCatalogCategoryList();
    }

    public function getFaultCodeList()
    {
        return $this->app->faultNotice->getCatalogFaultCodeList(input());
    }

    public function getFormOptions()
    {
        return $this->app->faultNotice->getCatalogFormOptions();
    }

    public function addCategory()
    {
        return $this->app->faultNotice->addCatalogCategory(input());
    }

    public function updateCategory()
    {
        return $this->app->faultNotice->updateCatalogCategory(input());
    }

    public function updateCategoryStatus()
    {
        $postData = input();
        return $this->app->faultNotice->updateCatalogCategoryStatus(
            intval($postData['category_id'] ?? 0),
            intval($postData['status'] ?? 0)
        );
    }

    public function addFaultCode()
    {
        return $this->app->faultNotice->addCatalogFaultCode(input());
    }

    public function updateFaultCode()
    {
        return $this->app->faultNotice->updateCatalogFaultCode(input());
    }

    public function updateFaultCodeStatus()
    {
        $postData = input();
        return $this->app->faultNotice->updateCatalogFaultCodeStatus(
            $postData['error_code'] ?? '',
            intval($postData['status'] ?? 0)
        );
    }

    public function updateFaultCodeNotice()
    {
        $postData = input();
        return $this->app->faultNotice->updateCatalogFaultCodeNotice(
            $postData['error_code'] ?? '',
            intval($postData['notice_enabled'] ?? 0)
        );
    }

    public function deleteFaultCode()
    {
        $postData = input();
        return $this->app->faultNotice->deleteCatalogFaultCode(
            $postData['error_code'] ?? ''
        );
    }
}
