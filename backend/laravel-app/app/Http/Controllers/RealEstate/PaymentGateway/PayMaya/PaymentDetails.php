<?php

namespace App\Http\Controllers\RealEstate\PaymentGateway\PayMaya;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

class PaymentDetails extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
        $secret_key = base64_encode(env('PAYMENT_GATEWAY_PAYMAYA_ID').':');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$secret_key,
        ])->get(env('PAYMENT_GATEWAY_PAYMAYA_URL').'/'.$request->payment_gateway_reference_number);

        return $response->json();
    }
}
