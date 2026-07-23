<?php

namespace app\wx\controller;

use app\AppFactory\AppFactory;
use think\facade\View;

class Coupon
{
    public function authorize()
    {
        return AppFactory::wx()->coupon->authorize(input());
    }

    public function callback()
    {
        return AppFactory::wx()->coupon->callback(input());
    }

    public function receive()
    {
        return AppFactory::wx()->coupon->receive(input());
    }

    public function page()
    {
        $pageData = AppFactory::wx()->coupon->getPageData(input());
        $host = rtrim(trim(strval(env('app.host'))), '/');
        $pageData['couponReceiveUrl'] = $host . '/wx/coupon/receive';
        $pageData['couponBackgroundUrl'] = $host . '/wx/coupon/background';
        $pageData['couponLogoUrl'] = $this->buildCouponLogoUrl(
            strval($pageData['couponLogo'] ?? ''),
            $host
        );
        if (!$pageData['couponLogoUrl']) {
            $pageData['couponLogoUrl'] = $host . '/wx/coupon/logo';
        }
        $pageData['couponLogoUrl'] .= (strpos($pageData['couponLogoUrl'], '?') === false ? '?' : '&')
            . 'logo_random=' . mt_rand(100000, 999999);
        View::assign($pageData);
        return View::fetch('coupon/index');
    }

    /**
     * 优惠券 Logo 属于静态资源，相对地址不经过正式环境的 /api 前缀。
     */
    protected function buildCouponLogoUrl($couponLogo, $host)
    {
        $couponLogo = trim(strval($couponLogo));
        if (!$couponLogo) return '';
        if (preg_match('#^https?://#i', $couponLogo)) return $couponLogo;

        $resourceHost = preg_replace('#/api/?$#i', '', rtrim(strval($host), '/'));
        return rtrim($resourceHost, '/') . '/' . ltrim($couponLogo, '/');
    }

    public function background()
    {
        $path = dirname(__DIR__) . '/view/coupon/images/bg.png';
        if (!is_file($path)) return response('', 404);
        return response(file_get_contents($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    public function logo()
    {
        $path = dirname(__DIR__) . '/view/coupon/images/logo_1.png';
        if (!is_file($path)) return response('', 404);
        return response(file_get_contents($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
