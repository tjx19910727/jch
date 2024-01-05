<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/31
 * Time: 11:24
 */

namespace app\AppFactory\Kernel\Traits\PaymentCharge;


trait AfterChargePaymentTrait
{
    public function paymentChargeSuccess()
    {
        $this->storeCharge['payment_status'] = 2;
        return $this->updateStoreCharge($this->storeCharge);
    }

    public function paymentChargeFail()
    {
        $this->storeCharge['payment_status'] = 3;
        return $this->updateStoreCharge($this->storeCharge);
    }
}