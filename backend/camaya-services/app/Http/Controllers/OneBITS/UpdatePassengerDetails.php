<?php

namespace App\Http\Controllers\OneBITS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\OneBITS\ValidateIfPassengerExists;
use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Passenger;

use Carbon\Carbon;

class UpdatePassengerDetails extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $validatedData = $request->validate([
            'booking_reference_number' => 'required',
            'guest_reference_number' => 'required'
        ]);

        $connection = DB::connection('camaya_booking_db');
        $connection->beginTransaction();

        $ticket = Ticket::where('reference_number', $validatedData['guest_reference_number'])
                        ->where('group_reference_number', $validatedData['booking_reference_number'])
                        ->first();

        $passenger = Passenger::where('id', $ticket->passenger_id)->first();

        $newData = $request->data;

        // Update 'age' on the Passenger
        if (isset($newData['age'])) {
            $passenger->age = $newData['age'];
            $passenger->save();
        }

        // Update 'amount' and 'discount' on the Ticket
        if (isset($newData['amount'])) {
            $ticket->amount = $newData['amount'];
        }
        if (isset($newData['discount'])) {
            $ticket->discount = $newData['discount'];
        } 

        $mergedData = [[
            'first_name' => isset($newData['first_name']) ? strtoupper($newData['first_name']) : strtoupper($passenger->first_name),
            'last_name' => isset($newData['last_name']) ? strtoupper($newData['last_name']) : strtoupper($passenger->last_name),
        ]];

        // Check if 'first_name' or 'last_name' is set in $newData, otherwise return early
        if (isset($newData['first_name']) || isset($newData['last_name'])) {
            $passengerValidator = new ValidateIfPassengerExists();
            $validationResult = $passengerValidator->checkIfPassengersExist($ticket->trip->seat_segment_id, $mergedData, $connection);

            if ($validationResult instanceof JsonResponse) {
                $connection->rollback();
                return $validationResult;
            }

            $passenger->fill($mergedData[0]);
            
        }
        $ticket->save();
        $passenger->save();
        $connection->commit();

        return response()->json(['message'=>'Passenger updated successfully']);
    }

}
