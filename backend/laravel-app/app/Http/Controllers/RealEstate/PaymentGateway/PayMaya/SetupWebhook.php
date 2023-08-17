<?php

namespace App\Http\Controllers\RealEstate\PaymentGateway\PayMaya;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

class SetupWebhook extends Controller
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

        // PAYMAYA INITIALIZATION

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$secret_key,
        ])->get(env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK'));

        self::deleteWebhooks($response->json());

        if ($response->ok()) {

            // if (!collect($response->json())->firstWhere('name', 'CHECKOUT_SUCCESS')) {
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic '.$secret_key,
                ])->post(env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK'), [
                    'name' => "CHECKOUT_SUCCESS",
                    'callbackUrl' => env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK_CALLBACKURL').'/paymaya/webhookCallback/success',
                ]);
            // }
    
            // if (!collect($response->json())->firstWhere('name', 'CHECKOUT_FAILURE')) {
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic '.$secret_key,
                ])->post(env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK'), [
                    'name' => "CHECKOUT_FAILURE",
                    'callbackUrl' => env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK_CALLBACKURL').'/paymaya/webhookCallback/error',
                ]);
            // }

            // if (!collect($response->json())->firstWhere('name', 'CHECKOUT_DROPOUT')) {
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic '.$secret_key,
                ])->post(env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK'), [
                    'name' => "CHECKOUT_DROPOUT",
                    'callbackUrl' => env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK_CALLBACKURL').'/paymaya/webhookCallback/dropout',
                ]);
            // }

        }

        return $response->json();
    }

    private function deleteWebhooks($json) {

        $secret_key = base64_encode(env('PAYMENT_GATEWAY_PAYMAYA_ID').':');

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$secret_key,
        ])->delete(env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK')."/".collect($json)->firstWhere('name', 'CHECKOUT_SUCCESS')['id']);

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$secret_key,
        ])->delete(env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK')."/".collect($json)->firstWhere('name', 'CHECKOUT_FAILURE')['id']);

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$secret_key,
        ])->delete(env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK')."/".collect($json)->firstWhere('name', 'CHECKOUT_DROPOUT')['id']);
    }
}
