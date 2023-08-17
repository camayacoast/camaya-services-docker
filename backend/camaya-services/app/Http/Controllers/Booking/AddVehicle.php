<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Booking\AddVehicleRequest;

use App\Models\Booking\Guest;
use App\Models\Booking\GuestVehicle;
use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;

use App\Models\AutoGate\Pass;

use Carbon\Carbon;

class AddVehicle extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(AddVehicleRequest $request)
    {
        //
        // return $request->all();

        $newVehicle = GuestVehicle::create([
            'booking_reference_number' => $request->booking_reference_number,
            'model' => $request->model,
            'plate_number' => $request->plate_number,
        ]);

        $booking = Booking::where('reference_number', $request->booking_reference_number)->first();

        // Create log
        ActivityLog::create([
            'booking_reference_number' => $request->booking_reference_number,
            'action' => 'add_vehicle',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has added new vehicle '. $request->model .' - '. $request->plate_number,
            'model' => 'App\Models\Booking\GuestVehicle',
            'model_id' => $newVehicle->id,
            'properties' => null,
            'created_by' => $request->user()->id,
        ]);

        return $newVehicle;
    }
}
