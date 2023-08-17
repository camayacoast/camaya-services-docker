<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Setting;

class UpdateDailyLimit extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $value = $request->value;
        // $selected_date = $request->selected_date;

        // $type = 'admin';

        // if ($request->type == 'SALES_DAILY_LIMIT') {
        //     $type = 'agent_portal';
        // }

        // if ($request->type == 'COMMERCIAL_DAILY_LIMIT') {
        //     $type = 'website';
        // }

        if (!is_int($value)) {
            return response()->json(['error' => 'ERROR_NOT_INT'], 400);
        }

        if ($value < 0) {
            return response()->json(['error' => 'ERROR_LESS_THAN_ZERO_NOT_ALLOWED'], 400);
        }

        // $daily_used = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
        //     ->where(function ($q) use ($selected_date) {
                
        //         $q->where('bookings.start_datetime', '=', $selected_date);
        //         $q->orWhere('bookings.start_datetime', '<=', $selected_date)
        //             ->where('bookings.end_datetime', '>=', $selected_date)
        //             ->where('bookings.end_datetime', '!=', $selected_date);
        //     })
        //     ->where('bookings.portal', $type)
        //     ->whereIn('bookings.status', ['confirmed', 'pending'])
        //     ->whereIn('guests.status', ['arriving', 'on_premise', 'checked_in'])
        //     ->whereNull('guests.deleted_at')
        //     ->where('guests.type','!=','infant')
        //     ->count();

        // if ($daily_used > $value) {
        //     return response()->json(['error' => 'ERROR_LESS_THAN_ZERO_NOT_ALLOWED'], 400);
        // }

        $DailyLimitEdit = Setting::where('code', $request->type)->first();

        if (!$DailyLimitEdit) {
            return response()->json(['error' => 'ERROR'], 400);
        }

        $DailyLimitEdit->update([
            'value' => $value,
        ]);

        return $DailyLimitEdit->refresh();
    }
}
