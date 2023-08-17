<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use App\Models\Booking\Setting;
use App\Models\Booking\DailyGuestLimit;
use App\Models\Booking\DailyGuestLimitNote;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Models\AutoGate\Pass;
use App\Models\AutoGate\Tap;

use App\Models\Booking\Invoice;

class DashboardData extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        ///////

        $selected_date = $request->date ?? Carbon::now()->setTimezone('Asia/Manila');

        $guests = Guest
        // ::whereHas('booking', function ($query) use ($selected_date) {
        //     $query->whereDate('start_datetime', '<=', $selected_date)
        //         ->whereDate('end_datetime', '>=', $selected_date);

        //     $query->whereIn('status', ['confirmed', 'pending']);
        // })
        ::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($selected_date) {
                // $q->whereDate('bookings.start_datetime', '<=', $selected_date)
                //     ->whereDate('bookings.end_datetime', '>=', $selected_date);

                $q->whereIn('bookings.status', ['confirmed', 'pending']);
                $q->where('bookings.start_datetime', '=', $selected_date);
                $q->orWhere('bookings.start_datetime', '<=', $selected_date)
                    ->where('bookings.end_datetime', '>=', $selected_date)
                    ->where('bookings.end_datetime', '!=', $selected_date);
        })
        ->with(['booking' => function ($q) {
            $q->select('reference_number','type','status', 'customer_id', 'start_datetime');
        }])
        ->select('guests.*')
        ->whereNull('guests.deleted_at')
        ->get();

        $total_arriving_guests = collect($guests)->where('booking.start_datetime', Carbon::now()->format('Y-m-d')." 00:00:00")->all();

        $total_day_tour_guests = collect($guests)->where('booking.type', 'DT')->all();

        $total_overnight_guests = collect($guests)->where('booking.type', 'ON')->all();

        $guest_per_day = Guest::join('bookings', 'guests.booking_reference_number', '=', 'bookings.reference_number')
                        ->whereRaw('DATE_FORMAT(bookings.start_datetime, "%Y-%m-%d") >= "'. Carbon::now()->format('Y-m-d').'"')
                        ->whereRaw('DATE_FORMAT(bookings.start_datetime, "%Y-%m-%d") <= "'. Carbon::now()->addDays(7)->format('Y-m-d').'"')
                        ->whereIn('bookings.status', ['confirmed', 'pending'])
                        ->groupBy('bookings.start_datetime')
                        ->selectRaw('COUNT(*) as count, DATE_FORMAT(bookings.start_datetime, "%Y-%m-%d") as date')
                        ->whereNull('deleted_at')
                        ->get();

        $guest_forecast = [];
        $period = CarbonPeriod::create(Carbon::now()->format('Y-m-d'), Carbon::now()->addDays(7)->format('Y-m-d'));

        foreach ($period as $date) {
            $guest_forecast[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $guest_per_day->firstWhere('date', $date->format('Y-m-d'))['count'] ?? 0,
            ];
        }
        
        /**
         * Tap arrival
         */
        // $checked_in_today = Tap::where('location', 'commercial_gate')
        //     ->where('type', 'entry')
        //     ->whereDate('tap_datetime', '<=', $selected_date)
        //     ->whereDate('tap_datetime', '>=', $selected_date)
        //     ->where('status', 'valid')
        //     ->count();

        // $commercial_gate_guest_entries = Tap::where('location', 'commercial_gate')
        //     ->where('type', 'entry')
        //     ->whereDate('tap_datetime', '<=', $selected_date)
        //     ->whereDate('tap_datetime', '>=', $selected_date)
        //     ->where('status', 'valid')
        //     ->pluck('code');

        // $commercial_gate_guest_entries_today = Guest::whereIn('reference_number', $commercial_gate_guest_entries)
        //     ->with(['booking' => function ($q) {
        //         $q->select('reference_number','type','status', 'customer_id', 'start_datetime', 'mode_of_transportation');
        //         $q->with('customer');
        //     }])
        //     ->whereNull('deleted_at')
        //     ->get();

        ///////
        // /**
        //  * Snack Pack
        //  */

        // $snack_packs = Pass::whereRaw('json_contains(interfaces, \'["snack_pack_redemption"]\')')
        //         ->whereDate('usable_at', '<=', $selected_date)
        //         ->whereDate('expires_at', '>=', $selected_date)
        //         ->withCount('valid_taps')
        //         ->with('guest')
        //         // ->join('bookings', 'bookings.reference_number', '=', 'passes.booking_reference_number')
        //         // ->where(function ($q) {
        //         //     $q->whereIn('bookings.status', ['confirmed', 'pending']);
        //         // })
        //         ->whereHas('booking', function ($q) {
        //             $q->whereIn('status', ['confirmed', 'pending']);
        //         })
        //         ->with(['booking' => function ($q) {
        //             $q->with('booking_payments');
        //             $q->select('reference_number','type','status', 'customer_id', 'start_datetime', 'mode_of_transportation', 'mode_of_payment');
        //             $q->with('customer');
        //         }])
        //         ->where('passes.status', '!=', 'voided')
        //         ->whereNull('deleted_at')
        //         ->get();

        // $snack_pack = Pass::whereRaw('json_contains(interfaces, \'["snack_pack_redemption"]\')')
        //     ->whereDate('usable_at', '<=', $selected_date)
        //     ->whereDate('expires_at', '>=', $selected_date)
        //     // ->join('bookings', 'bookings.reference_number', '=', 'passes.booking_reference_number')
        //     //     ->where(function ($q) {
        //     //         $q->whereIn('bookings.status', ['confirmed', 'pending']);
        //     // })
        //     ->whereHas('booking', function ($q) {
        //         $q->whereIn('status', ['confirmed', 'pending']);
        //     })
        //     ->where('passes.status', '!=', 'voided')
        //     ->whereNull('deleted_at')
        //     ->sum('count');

        // $redeemed_snack_pack_today = Tap::where('location', 'snack_pack_redemption')
        //     ->whereDate('tap_datetime', '<=', $selected_date)
        //     ->whereDate('tap_datetime', '>=', $selected_date)
        //     ->where('status', 'valid')
        //     ->count();
        //     // ->get();

        // $guests_redeemed_snack_pack_today = Tap::where('location', 'snack_pack_redemption')
        //     ->whereDate('tap_datetime', '<=', $selected_date)
        //     ->whereDate('tap_datetime', '>=', $selected_date)
        //     ->where('status', 'valid')
        //     ->with('guest')
        //     ->get();

        // $snack_pack_total = $snack_pack + $redeemed_snack_pack_today;

        $admin_guest_count = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($selected_date) {
                
                $q->where('bookings.start_datetime', '=', $selected_date);
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

        $sales_guest_count = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($selected_date) {
                
                $q->where('bookings.start_datetime', '=', $selected_date);
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

        $commercial_guest_count = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($selected_date) {
                
                $q->where('bookings.start_datetime', '=', $selected_date);
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

        // 

        //////
        /**
         * DGL Daily Guest Limit
         */
        $dgl = DailyGuestLimit::where('date', $selected_date)->get();
        $dgl_note = DailyGuestLimitNote::where('date', $selected_date)->first();

        return [
            // 'today' => Carbon::now()->format('Y-m-d'),
            // 'guests' => $guests,
            'total_arriving_guests' => count($total_arriving_guests),
            'total_day_tour_guests' => count($total_day_tour_guests),
            'total_overnight_guests' => count($total_overnight_guests),
            'total_guests_with_stayovers' => count($guests),
            'guest_forecast' => $guest_forecast,

            'admin_daily_limit' => Setting::where('code', 'ADMIN_DAILY_LIMIT')->first()['value'] ?? 0,
            'sales_daily_limit' => Setting::where('code', 'SALES_DAILY_LIMIT')->first()['value'] ?? 0,
            'commercial_daily_limit' => Setting::where('code', 'COMMERCIAL_DAILY_LIMIT')->first()['value'] ?? 0,

            'daily_guest_limit_per_day' => $dgl,
            'daily_guest_limit_note' => $dgl_note,

            'admin_daily_used' => $admin_guest_count,
            'sales_daily_used' => $sales_guest_count,
            'commercial_daily_used' => $commercial_guest_count,

            'trip_adult_max' => Setting::where('code', 'TRIP_ADULT_MAX')->first()['value'] ?? 0,
            'trip_kid_max' => Setting::where('code', 'TRIP_KID_MAX')->first()['value'] ?? 0,
            'trip_infant_max' => Setting::where('code', 'TRIP_INFANT_MAX')->first()['value'] ?? 0,
        ];
    }
}
