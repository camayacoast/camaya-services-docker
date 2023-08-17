<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'pay-online',
        'pay-online/*',
        'pay-online-return/*',
        'pay-online-postback/*',
        'api/booking/product/image-upload',
        'api/booking/product/add-image',
        'api/booking/package/add-image',
        'paymaya/webhookCallback/success',
        'paymaya/webhookCallback/error',
        'paymaya/booking/webhook/success',
        'paymaya/booking/webhook/error',
        'paymaya/booking/webhook/dropout',
        // 'api/get-user-by-token',
        'pesopay/datafeed',
    ];
}
