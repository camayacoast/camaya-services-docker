<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuestInformationSheetView extends Controller
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
                    ->with('customer')
                    ->with('inclusions')
                    ->with('room_reservations.room_type.property')
                    ->first();

        return view('pdf.booking.guest_info_sheet', [
            'booking' => $booking,
            'room_reservations_inclusion' => collect($booking->inclusions)->where('type', '=', 'room_reservation')
        ]);
    }
}
