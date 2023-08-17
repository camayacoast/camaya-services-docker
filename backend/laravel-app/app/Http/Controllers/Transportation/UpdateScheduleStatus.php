<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Schedule;
use App\Models\Transportation\Trip;
use App\Models\Transportation\SeatSegment;

use App\Models\AutoGate\Pass;

use App\Models\Booking\Guest;
use App\Models\Booking\Booking;

use Carbon\Carbon;

class UpdateScheduleStatus extends Controller
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
        $schedule = Schedule::find($request->id);

        if (!$schedule) {
            return response()->json(['error' => 'ERROR', 'message' => "Error"], 400);
        }

        $schedule->update(['status' => $request->new_status]);

        if ($request->new_status == 'cancelled') {
            /**
             * Cancel all ferry trips (Not yet implemented)
             */

            // Set trip record to cancel
            $trips = Trip::where('trip_number', $schedule->trip_number)->whereNotIn('status', ['no_show', 'cancelled'])->get();

            Trip::where('trip_number', $schedule->trip_number)
                    ->update([
                        'cancelled_at' => Carbon::now(),
                        'status' => 'cancelled',
                    ]);
            
            $booking_ref_nums = collect(collect($trips)->pluck('booking_reference_number')->all())->unique()->values()->all();

            // Remove passes
            Pass::where('mode', 'boarding')
                ->whereIn('booking_reference_number', $booking_ref_nums)
                ->where('type', 'LIKE', '%'.$schedule->trip_number)
                ->update([
                    'status' => 'voided',
                    'deleted_by' => $request->user()->id,
                    'deleted_at' => Carbon::now(),
                ]);

            // Set all bookings to OWN VEHICLE
            $bookings = Booking::where('mode_of_transportation', 'camaya_transportation')
                                ->whereIn('reference_number', $booking_ref_nums)
                                ->whereDoesntHave('trips', function ($q) {
                                    $q->whereIn('status', ['boarded', 'checked_in', 'pending']);
                                    $q->whereNotIn('status', ['no_show', 'cancelled']);
                                })
                                ->pluck('reference_number');

            Booking::whereIn('reference_number', $bookings)->update([
                'mode_of_transportation' => 'own_vehicle'
            ]);

            // Return allocation to segment
            if (count($trips)) {
                foreach ($trips as $trip) {
                    $guest = Guest::where('reference_number', $trip['guest_reference_number'])->first();

                    if ($guest->type != 'infant') {
                        SeatSegment::where('id', $trip['seat_segment_id'])
                                    ->decrement('used');
                    }
                }
            }
        }

        return $schedule;
    }
}
