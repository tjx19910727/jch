<?php

namespace app\AppFactory\Wx\Official;

use Fastknife\Service\BlockPuzzleCaptchaService;
use Fastknife\Utils\AesUtils;

class CouponSliderCaptchaService extends BlockPuzzleCaptchaService
{
    /**
     * 前端只提交滑动距离，坐标加密在服务端完成，避免额外引入 CryptoJS。
     */
    public function checkPlainPoint($token, $x)
    {
        $this->setOriginData($token);
        $pointJson = AesUtils::encrypt(json_encode([
            'x' => intval($x),
            'y' => 5,
        ], JSON_UNESCAPED_UNICODE), strval($this->originData['secretKey']));

        return $this->check($token, $pointJson);
    }
}
