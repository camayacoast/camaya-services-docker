<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use PayMaya\PayMayaSDK;
use PayMaya\Core\CheckoutAPIManager;

use App\Models\Booking\GeneratedVoucher;
use App\Http\Controllers\Booking\PaymentFailed;

class VoucherPaymentFailed extends Controller
{
    public function index(Request $request)
    {
        switch ($request->provider) {
            // add another provider in switchcase in the future implementation
            default:
                $this->paymaya($request);
                break;
        }
    }

    public function paymaya($request)
    {
        PayMayaSDK::getInstance()->initCheckout(env('MAYA_PUBLIC_API_KEY'), env('MAYA_SECRET_API_KEY'), env('MAYA_API_ENDPOINT_ENV'));

        $generated_vouchers = GeneratedVoucher::with('voucher')->with('customer')->where(
            'transaction_reference_number', $request->transaction_reference_number
        )->whereNull('paid_at')->get();

        

        // Pulling payment details in paymaya domain
        $APIManager = new CheckoutAPIManager();
        $response = $APIManager->retrieveCheckout($generated_vouchers[0]->checkout_id);
        $responseArr = json_decode($response, true);

        $error_code = $responseArr['paymentDetails']['responses']['efs']['unhandledError'][0]['code'] ?? '9999';

        $paymentFailed = new PaymentFailed();
        $description = $paymentFailed->paymayaErrorMessage($error_code);

        $update = GeneratedVoucher::where('transaction_reference_number', $request->transaction_reference_number)
            -> update([
                'description' => $description
            ]);

        return redirect()->to(
            env('CAMAYA_BOOKING_PORTAL_URL').'/voucher-payment-failed?transaction_reference_number=' . $request->transaction_reference_number
        )->send();
    }
}
