<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;

use App\Mail\Booking\BookingConfirmation;
use App\Mail\Booking\ResendBookingConfirmation as ResendBookingConfirmationMail;
use Illuminate\Support\Facades\Mail;

// use PDF;
use Barryvdh\DomPDF\Facade as PDF;

class ResendBookingConfirmation extends Controller
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
        // return $request->all();
        // $booking = Booking::where('reference_number', $request->ref_no)->with('customer:id,email')->first();
        $booking = Booking::where('reference_number', $request->booking_reference_number)
                    ->with('bookedBy')
                    ->with('customer')
                    ->with(['guests' => function ($q) {
                        $q->with('tee_time.schedule');
                        $q->with('guestTags');
                        $q->with('tripBookings.schedule.transportation');
                        $q->with('tripBookings.schedule.route.origin');
                        $q->with('tripBookings.schedule.route.destination');
                    }])
                    ->with('inclusions.packageInclusions')
                    ->with('inclusions.guestInclusion')
                    ->with('invoices')
                    ->withCount(['invoices as invoices_grand_total' => function ($q) {
                        $q->select(\DB::raw('sum(grand_total)'));
                    }])
                    ->withCount(['invoices as invoices_balance' => function ($q) {
                        $q->select(\DB::raw('sum(balance)'));
                    }])
                    ->first();

        // return $booking;
            if ($booking->mode_of_transportation == 'own_vehicle') {
                $booking->load('guestVehicles');
            }

            $camaya_transportations = [];

            if ($booking->mode_of_transportation == 'camaya_transportation') {
                $booking->load('camaya_transportation');
    
                $camaya_transportations = \App\Models\Transportation\Schedule::whereIn('trip_number', collect($booking['camaya_transportation'])->unique('trip_number')->pluck('trip_number')->all())
                                    ->with('transportation')
                                    ->with('route.origin')
                                    ->with('route.destination')
                                    ->get();

            }
        
        if (!$booking) {
            return response()->json(['error' => 'BOOKING_NOT_FOUND', 'message'=>'booking not found'], 400);
        }
        
        $emailToSend = new BookingConfirmation($booking, $camaya_transportations);

        if ($booking->status == 'pending') {
            $emailToSend = new ResendBookingConfirmationMail($booking, $camaya_transportations);
        }

        $additional_emails = [];

        if (isset($booking->additionalEmails)) {
            $additional_emails = collect($booking->additionalEmails)->pluck('email')->all();
        }

        $mail = Mail::to($booking->customer->email)
                        ->cc($additional_emails)
                        ->send($emailToSend);

        if (!$mail) {
            return response()->json(['error' => 'FAILED_TO_SEND_EMAIL', 'message'=>'Sending failed.'], 400);
        }

        return response()->json(['status' => 'OK', 'message'=>''], 200);
        
    }
}
