<?php

namespace App\Http\Controllers\OneBITS\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

class PaymentBackend extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // $response = new IPay88Response($request);

        // logic to check if order has been updated before
        $response = $request->all();

        Log::channel('onebitspayment')->info("BackendResponse");
        Log::channel('onebitspayment')->info($response);

        return $response;
    }
}
