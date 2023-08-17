<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrintBoardingPassOnePDF extends Controller
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
        $booking = \App\Models\Booking\Booking::where('reference_number', $request->booking_reference_number)
                                // ->with('bookedBy')
                                // ->with('customer')
                                ->with(['guests' => function ($q) {
                                    $q->with('tee_time.schedule');
                                    $q->with('guestTags');
                                    $q->with('tripBookings.schedule.transportation');
                                    $q->with('tripBookings.schedule.route.origin');
                                    $q->with('tripBookings.schedule.route.destination');
                                }])
                                // ->with('inclusions.packageInclusions')
                                // ->with('inclusions.guestInclusion')
                                // ->with('invoices')
                                // ->withCount(['invoices as invoices_grand_total' => function ($q) {
                                //     $q->select(\DB::raw('sum(grand_total)'));
                                // }])
                                // ->withCount(['invoices as invoices_balance' => function ($q) {
                                //     $q->select(\DB::raw('sum(balance)'));
                                // }])
                                ->first();

        return \PDF::loadView('pdf.booking.boarding_pass2', ['guests' => $booking->guests])->stream();
    }
}
