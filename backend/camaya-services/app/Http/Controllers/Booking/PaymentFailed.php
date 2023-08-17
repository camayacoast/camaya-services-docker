<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking\Booking;
use App\Models\Booking\Payment;
use App\Models\Booking\Invoice;
use App\Models\Booking\ActivityLog;

use PayMaya\PayMayaSDK;
use PayMaya\Core\CheckoutAPIManager;

use Carbon\Carbon;

class PaymentFailed extends Controller
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
        
        $booking = Booking::where('reference_number', $request->booking_reference_number)
            ->with('pending_payments')->with('invoices')->first();

        if( $booking ) {

            $payments = $booking->pending_payments;

            $checkout_id = '';
            foreach( $payments as $payment ) {
                if( $payment->checkout_id !== '' ) {
                    $checkout_id = $payment->checkout_id;
                }
            }

            if( $checkout_id === '' ) {
                echo json_encode([
                    'error' => '404',
                    'message' => 'Checkout id is missing'
                ]);
            }

            // Pulling payment details in paymaya domain
            $APIManager = new CheckoutAPIManager();
            $response = $APIManager->retrieveCheckout($checkout_id);
            $responseArr = json_decode($response, true);

            // Handling of error code if keys not exists 404 will be thrown
            $error_code = $responseArr['paymentDetails']['responses']['efs']['unhandledError'][0]['code'] ?? '9999';

            $booking_id = $booking->id;
            $pending_payments = $booking->pending_payments;
            $invoices = $booking->invoices;

            // Append the error message at the end of user's booking remarks
            // if message is exists replace it with new error message
            $regex = '/\([^)]*\)/';
            $paymaya_response_message = '(' . $this->paymayaErrorMessage($error_code) . ')';
            $booking_remarks = preg_replace($regex, '', $booking->remarks);

            foreach( $pending_payments as $payment ) {
                Payment::where('id', $payment->id)->update([
                    'status' => 'cancelled',
                    'remarks' => $this->paymayaErrorMessage($error_code)
                ]);
            }

            if( $error_code !== '9999' ) {
                ActivityLog::create([
                    'booking_reference_number' => $request->booking_reference_number,
                    'action' => 'online_payment',
                    'description' => 'Paymaya Online Payment Failed: ' . $paymaya_response_message,
                    'model' => 'App\Models\Booking\Booking',
                    'model_id' => $booking_id,
                    'properties' => null,
                    'created_by' => null,
                ]);
            }

            return redirect()->to(
                env('CAMAYA_BOOKING_PORTAL_URL').'/payment-failed?booking_reference_number=' . $request->booking_reference_number
            )->send();

        } else {
            echo json_encode([
                'error' => '404',
                'message' => 'Booking with ref.# ' . $request->booking_reference_number . ' is not found in our system.'
            ]);
        }
    }

    public function paymayaErrorMessage($code) {

        $default_message = $code . ' - ' . 'A problem is encountered. Please contact your system administrator.';
        $messages = [
            '2051' => 'Payment Failed due to Insufficient balance',
            '2043' => 'Stolen Card',
            '2059' => 'Issuer Suspected Fraud',
            'ACQ084' => 'Transaction was blocked by the Acquirer',
            'ACQ047' => 'MPI Enrollment/Authentication Verification Error',
            '9999' => 'Error Response Not found' // If code is not exists in the response
        ];

        $message = ( isset($messages[$code]) ) ? $messages[$code] : $default_message;

        return $message;
    }
}
