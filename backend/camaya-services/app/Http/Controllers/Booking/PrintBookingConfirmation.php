<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Booking\ConfirmBookingRequest;

use App\Models\Booking\Booking;
use App\Models\Hotel\RoomReservation;
use App\Models\Transportation\Trip;

use Carbon\Carbon;

// use PDF;
use Barryvdh\DomPDF\Facade as PDF;

class PrintBookingConfirmation extends Controller
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

        $booking = Booking::where('reference_number', $request->booking_reference_number)
                ->with('bookedBy')
                ->with('customer')
                ->with(['guests' => function ($q) {
                    $q->with('tee_time.schedule');
                    $q->with('guestTags');
                    $q->with(['tripBookings.schedule' => function ($q) {
                        $q->with('transportation');
                        $q->with('route.origin');
                        $q->with('route.destination');
                    }]);
                    
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

            if ($booking->status == 'confirmed') {
                return \PDF::loadView('pdf.booking.booking_confirmation', ['booking' => $booking, 'camaya_transportations' => $camaya_transportations])->stream();
            } else if ($booking->status == 'pending') {
                return \PDF::loadView('pdf.booking.booking_pending', ['booking' => $booking, 'camaya_transportations' => $camaya_transportations])->stream();
            }
    }
}
