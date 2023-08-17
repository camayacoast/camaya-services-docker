<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use App\Models\Booking\GuestVehicle;
use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;

use App\Models\AutoGate\Pass;

use Carbon\Carbon;

class DeleteVehicle extends Controller
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

        $guest = GuestVehicle::find($request->guest_id);

        // Update vehicle details deleted at and deleted by

        $guest_vehicles->update([
            'deleted_at' => Carbon::now(),
            'deleted_by' => $request->user()->id
        ]);

        //Delete Vehicle


        // Logs the deletion
        // Create log
        // use App\Models\Booking\ActivityLog;
        ActivityLog::create([
            'booking_reference_number' => $vehicleToDelete->booking_reference_number,
            'action' => 'update_vehicle',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has deleted the vehicle "'.$vehicleToEdit['model'].' - '.$vehicleToEdit['plate_number'].'".',
            'model' => 'App\Models\Booking\GuestVehicle',
            'model_id' => $vehicleToDelete->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        return $vehicleToDelete;
    }
}
