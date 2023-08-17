<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking\Guest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Models\AutoGate\Pass;
use App\Models\AutoGate\Tap;

use App\Models\Booking\Invoice;

class DTTReport extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        $start_date = $request->start_date ?? Carbon::now()->setTimezone('Asia/Manila');
        $end_date = $request->end_date ?? Carbon::now()->setTimezone('Asia/Manila');

        $dtt_arriving_guests = Guest::whereHas('booking', function ($query) use ($start_date, $end_date) {
                $query->whereDate('start_datetime', '<=', $end_date)
                    ->whereDate('start_datetime', '>=', $start_date);
                
                $query->whereIn('type', ['DT']);

                $query->whereIn('status', ['confirmed', 'pending']);
            })
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->with(['active_trips' => function ($q) {
                $q->join('schedules', 'schedules.trip_number', '=', 'trips.trip_number');
                $q->join('routes', 'routes.id', '=', 'schedules.route_id');
                $q->join('locations as destination', 'destination.id', '=', 'routes.destination_id');
                $q->select('trips.guest_reference_number', 'trips.trip_number', 'trips.status','destination.code as destination_code');
            }])
            ->with(['booking' => function ($q) {
                $q->with('guestVehicles');
                $q->with('customer');
                $q->with('tags');
                $q->with('invoices');
                $q->with('booking_payments');
                $q->select('id', 'reference_number','type','status', 'customer_id', 'start_datetime', 'end_datetime', 'mode_of_transportation', 'mode_of_payment');
            }])
            // ->with('active_trips')
            ->with('guestTags')
            // ->with('commercialEntry:code,tap_datetime')
            ->whereNull('deleted_at')
            ->get();

        $total_dtt_revenue = Guest::whereHas('booking', function ($query) use ($start_date, $end_date) {
                $query->whereDate('start_datetime', '<=', $end_date)
                    ->whereDate('start_datetime', '>=', $start_date);
                
                $query->whereIn('type', ['DT']);
                $query->whereIn('status', ['confirmed', 'pending']);
            })
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->with(['booking' => function ($q) {
                $q->with('invoices');
                $q->with('booking_payments');
                $q->select('id', 'reference_number','type','status', 'customer_id', 'start_datetime', 'end_datetime');
            }])

            ->whereNull('deleted_at')
            ->get();

        //////
        /**
         * Snack Pack
         */

        $snack_packs = Pass::whereRaw('json_contains(interfaces, \'["snack_pack_redemption"]\')')
                ->whereDate('usable_at', '<=', $end_date)
                ->whereDate('expires_at', '>=', $start_date)
                ->withCount('valid_taps')
                ->with('guest')
                ->whereHas('booking', function ($q) {
                    $q->whereIn('status', ['confirmed', 'pending']);
                })
                ->with(['booking' => function ($q) {
                    $q->with('booking_payments');
                    $q->select('reference_number','type','status', 'customer_id', 'start_datetime', 'mode_of_transportation', 'mode_of_payment');
                    $q->with('customer');
                }])
                ->where('status', '!=', 'voided')
                ->whereNull('deleted_at')
                ->get();

        $snack_pack = Pass::whereRaw('json_contains(interfaces, \'["snack_pack_redemption"]\')')
            ->whereDate('usable_at', '<=', $end_date)
            ->whereDate('expires_at', '>=', $start_date)
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['confirmed', 'pending']);
            })
            ->where('status', '!=', 'voided')
            ->whereNull('deleted_at')
            ->sum('count');

        // $redeemed_snack_pack_today = Tap::where('location', 'snack_pack_redemption')
        //     ->where('tap_datetime', '<=', $end_date)
        //     ->where('tap_datetime', '>=', $start_date)
        //     ->where('status', 'valid')
        //     ->count();

        // $guests_redeemed_snack_pack_today = Tap::where('location', 'snack_pack_redemption')
        //     ->where('tap_datetime', '<=', $end_date)
        //     ->where('tap_datetime', '>=', $start_date)
        //     ->where('status', 'valid')
        //     ->with('guest')
        //     ->get();

        // $snack_pack_total = $snack_pack + $redeemed_snack_pack_today;
        $snack_pack_total = 0;


        //////

        return [
            'dtt_arriving_guests' => $dtt_arriving_guests,

            'total_dtt_revenue' => $total_dtt_revenue,

            'snack_pack_count' => $snack_pack,
            // 'redeemed_snack_pack_today' => $redeemed_snack_pack_today,
            'redeemed_snack_pack_today' => [],

            'snack_pack_list' => $snack_packs,
            'snack_pack_total' => $snack_pack_total,
            
            // 'guests_redeemed_snack_pack_today' => $guests_redeemed_snack_pack_today
            'guests_redeemed_snack_pack_today' => [],

        ];
    }
}
