<?php

namespace App\Http\Controllers\OneBITS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Passenger;

use Carbon\Carbon;

class GetPassengersByGroupReferenceNumber extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $passengers = [];

        try{
            $passengers = Ticket::with(['passenger' => function ($query) {
                $query->select('id', 'guest_reference_number', 'first_name', 'last_name', 'age');
            }])
            ->where('group_reference_number', $request->groupReferenceNumber)
            ->get();

        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'passengers' => $passengers
        ], 200);
    }
}
