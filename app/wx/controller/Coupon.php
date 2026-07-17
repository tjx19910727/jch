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
        View::assign(AppFactory::wx()->coupon->getPageData(input()));
        return View::fetch('coupon/index');
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
}
