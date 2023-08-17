<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\ActivityLog;
use App\Models\Booking\Booking;
use App\Models\Booking\Payment;

use PayMaya\PayMayaSDK;
use PayMaya\Core\CheckoutAPIManager;


class PaymentCancel extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $payment = Payment::where('payment_reference_number', $request->payment_reference_number)->first();

        switch ($request->provider) {
            case 'paymaya':
                $this->paymaya($payment, $request);
                break;
            default:
                $this->paypal($payment, $request);
                break;
        }
    }

    public function paymaya($payment, $request)
    {
        if( $payment && $payment->status === 'pending' ) {

            $booking = Booking::where('reference_number', $payment->booking_reference_number)->first();

            Payment::where('payment_reference_number', $request->payment_reference_number)
            ->update([
                'status' => 'cancelled',
            ]);

            ActivityLog::create([
                'booking_reference_number' => $payment->booking_reference_number,
                'action' => 'online_payment',
                'description' => 'Paymaya Online Payment Cancelled with Payment Ref#: ' . $request->payment_reference_number,
                'model' => 'App\Models\Booking\Booking',
                'model_id' => $booking->id,
                'properties' => null,
                'created_by' => null,
            ]);

            return redirect()->to(
                env('CAMAYA_BOOKING_PORTAL_URL').'/payment-cancelled?booking_reference_number=' . $payment->booking_reference_number
            )->send();
        }

        // redirect to booking portal if payment record not exists
        return redirect()->to(env('CAMAYA_BOOKING_PORTAL_URL'))->send();
    }

    public function paypal($payment, $request)
    {
        if ($request->token && $payment->status == 'pending') {
            //
            // Update transaction data into the database
            Payment::where('payment_reference_number', $request->payment_reference_number)
            ->update([
                'status' => 'cancelled',
            ]);

            return redirect()->to(
                env('CAMAYA_BOOKING_PORTAL_URL').'/payment-cancelled?booking_reference_number=' . $payment->booking_reference_number
            )->send();
        }
        
        // return 'User is canceled the payment.';
        return redirect()->to(env('CAMAYA_BOOKING_PORTAL_URL'))->send();
    }
}
