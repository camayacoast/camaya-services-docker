<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use App\Models\Transportation\Passenger;
use App\Models\Booking\ActivityLog;

class UpdateGuest extends Controller
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

        $guestToEdit = Guest::find($request->id);

        $infant_min_age = 0;
        $infant_max_age = 2;

        $kid_min_age = 3;
        $kid_max_age = 11;

        $adult_min_age = 12;
        $adult_max_age = 100;

        $guest_type = $guestToEdit->type;
        $updated_guest_type = '';

        if ($request->age >= $infant_min_age && $request->age <= $infant_max_age) {
            $updated_guest_type = 'infant';
        } else if ($request->age >= $kid_min_age && $request->age <= $kid_max_age) {
            $updated_guest_type = 'kid';
        } else if ($request->age >= $adult_min_age && $request->age <= $adult_max_age) {
            $updated_guest_type = 'adult';
        }

        if ($guest_type != $updated_guest_type) {
            return response()->json(['error' => 'Age does not qualify to guest type.'], 400);
        }

        // Create log
        // use App\Models\Booking\ActivityLog;
        ActivityLog::create([
            'booking_reference_number' => $guestToEdit->booking_reference_number,

            'action' => 'update_guest',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has updated the guest from '.$guestToEdit['first_name'].' '.$guestToEdit['last_name'].' ('.$guestToEdit['age'].') to '.$request->first_name.' '.$request->last_name.' ('.$request->age.').',
            'model' => 'App\Models\Booking\Guest',
            'model_id' => $guestToEdit->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        /**
         * Update guest
         */

        Guest::where('id', $request->id)
            ->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'age' => $request->age,
                'nationality' => $request->nationality,
                'type' => $guest_type,
            ]);

        /**
         * Update passenger name
         */
        Passenger::where('guest_reference_number', $guestToEdit->reference_number)
                        ->update([
                            'first_name' => $request->first_name,
                            'last_name' => $request->last_name,
                            'age' => $request->age,
                            'nationality' => $request->nationality,
                            'type' => $guest_type,
                        ]);

        

        return $guestToEdit->refresh();
    }
}
