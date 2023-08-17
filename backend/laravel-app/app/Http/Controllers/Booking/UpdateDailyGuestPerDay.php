<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use App\Models\Booking\DailyGuestLimit;
use App\Models\Booking\DailyGuestLimitNote;

class UpdateDailyGuestPerDay extends Controller
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

        if (!$request->date || $request->admin < 0 || $request->commercial < 0 || $request->sales < 0) {
            return false;
        }

        $admin_guest_count = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($request) {
                
                $q->where('bookings.start_datetime', '=', $request->date);
                // $q->orWhere('bookings.start_datetime', '<=', $selected_date)
                //     ->where('bookings.end_datetime', '>=', $selected_date)
                //     ->where('bookings.end_datetime', '!=', $selected_date);
                $q->whereDoesntHave('booking.tags', function ($q) {
                    $q->where('name', 'Ferry Only');
                });

            })
            ->where('bookings.portal', 'admin')
            ->whereIn('bookings.status', ['confirmed', 'pending'])
            ->whereIn('guests.status', ['arriving', 'on_premise', 'checked_in'])
            ->whereNull('guests.deleted_at')
            ->where('guests.type','!=','infant')
            ->count();

        $commercial_guest_count = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($request) {
                
                $q->where('bookings.start_datetime', '=', $request->date);
                // $q->orWhere('bookings.start_datetime', '<=', $selected_date)
                //     ->where('bookings.end_datetime', '>=', $selected_date)
                //     ->where('bookings.end_datetime', '!=', $selected_date);
                $q->whereDoesntHave('booking.tags', function ($q) {
                    $q->where('name', 'Ferry Only');
                });
            })
            ->where('bookings.portal', 'website')
            ->whereIn('bookings.status', ['confirmed', 'pending'])
            ->whereIn('guests.status', ['arriving', 'on_premise', 'checked_in'])
            ->whereNull('guests.deleted_at')
            ->where('guests.type','!=','infant')
            ->count();

        $sales_guest_count = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($request) {
                
                $q->where('bookings.start_datetime', '=', $request->date);
                // $q->orWhere('bookings.start_datetime', '<=', $selected_date)
                //     ->where('bookings.end_datetime', '>=', $selected_date)
                //     ->where('bookings.end_datetime', '!=', $selected_date);
                $q->whereDoesntHave('booking.tags', function ($q) {
                    $q->where('name', 'Ferry Only');
                });
            })
            ->where('bookings.portal', 'agent_portal')
            ->whereIn('bookings.status', ['confirmed', 'pending'])
            ->whereIn('guests.status', ['arriving', 'on_premise', 'checked_in'])
            ->whereNull('guests.deleted_at')
            ->where('guests.type','!=','infant')
            ->count();

        if ($request->admin < $admin_guest_count) {
            return response()->json(['message' => "Failed to adjust limit on admin."], 400);
        }

        if ($request->commercial < $commercial_guest_count) {
            return response()->json(['message' => "Failed to adjust limit on commercial."], 400);
        }

        if ($request->sales < $sales_guest_count) {
            return response()->json(['message' => "Failed to adjust limit on sales."], 400);
        }

        DailyGuestLimit::updateOrCreate([
            'category' => 'Admin',
            'date' => $request->date
        ],[
            'limit' => $request->admin > 0 ? $request->admin : 0,
            'status' => 'approved',
            'created_by' => $request->user()->id,
            // 'created_at' => now(),
        ]);

        DailyGuestLimit::updateOrCreate([
            'category' => 'Commercial',
            'date' => $request->date
        ],[
            'limit' => $request->commercial > 0 ? $request->commercial : 0,
            'status' => 'approved',
            'created_by' => $request->user()->id,
            // 'created_at' => now(),
        ]);

        DailyGuestLimit::updateOrCreate([
            'category' => 'Sales',
            'date' => $request->date
        ],[
            'limit' => $request->sales > 0 ? $request->sales : 0,
            'status' => 'approved',
            'created_by' => $request->user()->id,
            // 'created_at' => now(),
        ]);

        if (isset($request->remarks) || $request->remarks == "") {
            DailyGuestLimitNote::updateOrCreate([
                'date' => $request->date. ' 00:00:00'
            ],[
                'note' => $request->remarks,
                'updated_by' => $request->user()->id,
                // 'created_at' => now(),
            ]);
        }
        
        return response()->json([], 200);

    }
}
