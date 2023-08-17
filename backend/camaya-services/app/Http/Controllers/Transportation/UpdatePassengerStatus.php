<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Trip;
use App\Models\Transportation\SeatSegment;
use App\Models\AutoGate\Pass;

use App\Models\Booking\Guest;
use App\Models\Booking\Booking;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

class UpdatePassengerStatus extends Controller
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
        $trip = Trip::find($request->id);

        if (!$trip) {
            return response()->json(['error' => 'PASSENGER_NOT_FOUND', 'message'=>''], 400);
        }

        $pass = Pass::where('guest_reference_number', $trip->guest_reference_number)
                        ->where( function ($q) use ($trip) {
                                $q->whereHas('trip', function ($q) use ($trip) {
                                    $q->where('id', $trip->id);
                                });
                        })
                        ->first();

        if (!$pass) {
            return response()->json(['error' => 'PASS_NOT_FOUND', 'message'=>''], 400);
        }

        $guest = Guest::where('reference_number', $trip->guest_reference_number)->first();

        $seat_number = $trip->seat_number;
        $seat_segment_id = $trip->seat_segment_id;

        switch ($request->new_status) {
            case 'arriving':
                    Pass::where('id', $pass->id)
                        ->update([
                            'count' => 1,
                            'status' => 'created'
                        ]);
                    // Should GET the allocation segment to schedule
                break;
            case 'pending':
                    Pass::where('id', $pass->id)
                        ->update([
                            'count' => 1,
                            'status' => 'created'
                        ]);
                    // Should GET the allocation segment to schedule
                break;
            case 'checked_in':
                    Pass::where('id', $pass->id)
                        ->update([
                            'count' => 1,
                            'status' => 'created'
                        ]);
                    // Should GET the allocation segment to schedule
                    // Marked as arriving
                    Guest::where('reference_number', $trip->guest_reference_number)
                            ->update([
                                'status' => 'arriving',
                                'updated_at' => Carbon::now(),
                            ]);
                break;
            case 'boarded':

                    // Get booking
                    $booking = Booking::where('reference_number', $pass['booking_reference_number'])->first();

                    if ($booking->status == 'confirmed') {
                        Pass::where('id', $pass->id)
                            ->update([
                                'count' => 0,
                                'status' => 'consumed'
                            ]);
                        // Should GET the allocation segment to schedule

                        // Marked as checked-in
                        Guest::where('reference_number', $trip->guest_reference_number)
                                ->update([
                                    'status' => 'checked_in',
                                    'updated_at' => Carbon::now(),
                                ]);

                        Trip::where('id', $pass->trip_id)
                                ->update([
                                    'status' => 'boarded',
                                    'boarded_at' => Carbon::now(),
                                ]);

                        Log::info('Manual boarding: '. $trip->guest_reference_number);
                    } else {
                        Log::info('Booking is not confirmed for Manual boarding: '. $trip->guest_reference_number .' - '. $pass['booking_reference_number']);
                        return response()->json(['error' => 'BOOKING_NOT_CONFIRMED', 'message'=>'Booking is not CONFIRMED.'], 400);
                    }
                break;
            case 'no_show':
                    Pass::where('id', $pass->id)
                        ->update([
                            'status' => 'voided'
                        ]);

                    // Should return the allocation segment to schedule
                    // $trip = Trip::find($request->id);
                    if ($guest->type != 'infant') {
                        SeatSegment::where('id', $trip['seat_segment_id'])
                            // ->decrement('used');
                            ->update(['used' => \DB::raw('IF(used <= 0, 0, used - 1)')]);
                    }

                    $seat_number = null;
                    $seat_segment_id = null;
                break;
            case 'cancelled':
                    Pass::where('id', $pass->id)
                        ->update([
                            'status' => 'voided'
                        ]);
                        
                    // Should return the allocation segment to schedule
                    if ($guest->type != 'infant') {
                        SeatSegment::where('id', $trip['seat_segment_id'])
                                // ->decrement('used');
                                ->update(['used' => \DB::raw('IF(used <= 0, 0, used - 1)')]);
                    }

                    $seat_number = null;
                    $seat_segment_id = null;
                break;
        } 

        $trip->update([
            'status' => $request->new_status,
            'seat_number' => $seat_number,
            'seat_segment_id' => $seat_segment_id,
        ]);

        $passengerList = Trip::where('trip_number', $trip->trip_number)
                    ->with(['schedule' => function ($q) {
                        // $q->where('trip_date', $date);
                        $q->with('transportation');
                        $q->with('route.origin');
                        $q->with('route.destination');
                    }])
                    ->with(['passenger' => function ($q) {
                        $q->orderBy('last_name');
                    }])
                    ->with(['booking' => function ($q) {
                        $q->with('customer');
                        $q->with('tags');
                    }])
                    ->where('ticket_reference_number', '1')
                    ->get();

        return [$trip, $pass, 'passengerList' => $passengerList];
    }
}
