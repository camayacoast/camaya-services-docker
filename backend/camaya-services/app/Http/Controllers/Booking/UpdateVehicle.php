<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use App\Models\Booking\GuestVehicle;
use App\Models\Booking\ActivityLog;

class UpdateVehicle extends Controller
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

        $vehicleToEdit = GuestVehicle::find($request->id);

        // Create log
        // use App\Models\Booking\ActivityLog;
        ActivityLog::create([
            'booking_reference_number' => $vehicleToEdit->booking_reference_number,
            'action' => 'update_vehicle',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has updated the vehicle details from "'.$vehicleToEdit['model'].' - '.$vehicleToEdit['plate_number'].'" to "'.$request->model.' - '.$request->plate_number.'".',
            'model' => 'App\Models\Booking\GuestVehicle',
            'model_id' => $vehicleToEdit->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        $vehicleToEdit->update([
            'model' => $request->model,
            'plate_number' => $request->plate_number,
        ]);

        return $vehicleToEdit->refresh();
    }
}
