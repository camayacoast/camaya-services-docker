<?php

namespace App\Http\Controllers\AFParkingMonitoring;

use App\Http\Controllers\Controller;
use App\Models\AutoGate\Tap;
use App\Models\Booking\Guest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Status extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        if (env('APP_ENV') == 'production') {
            return false;
        }

        if (!Cache::has('af-parking-monitoring-mode')) {
            Cache::forever('af-parking-monitoring-mode', 1);
        }

        $start_date = Carbon::now()->subDays(30)->startOfDay();
        $end_date = Carbon::now()->endOfDay();        

        /*
            mode
            1 = Normal Exit
            2 = Exit By Hotel Status
        */
        $mode = Cache::get('af-parking-monitoring-mode');
        $total = 21;
        $location = 'parking_gate';
        $entry_codes = [];
        $entries_count = Tap::query()
            ->whereBetween('tap_datetime', [$start_date, $end_date])
            ->where('location', '=', $location)
            ->where('type', 'entry')
            ->where('status', 'valid')
            ->orderBy('id', 'desc')
            ->with(['guest.booking.room_reservations_no_filter'])
            ->get()
            ->filter(function ($value, $key) use (&$entry_codes, $location, $mode) {
                $checkout = true;

                if ($mode === "1") {
                    $room_reservations = $value["guest"]["booking"]["room_reservations_no_filter"];
                    foreach($room_reservations as $room_reservation) {
                        if ($room_reservation["status"] !== "checked_out") {
                            $checkout = false;
                        }
                    }
                }

                // Log::debug($value);
                // Log::debug($checkout);

                $has_exit_tap = Tap::query()
                    ->where('tap_datetime', '>', $value->tap_datetime)
                    ->where('code', '=', $value->code)
                    ->where('location', '=', $location)
                    ->where('type', '=', 'exit')
                    ->where('status', '=', 'valid')
                    ->first();

                if ($mode === "2" && $checkout) {
                    return false;
                } else if ($mode === "2" && !$checkout && in_array($value->code, $entry_codes)) {
                    return false;
                } else if ($mode === "1" && in_array($value->code, $entry_codes) || $mode === "1" && $has_exit_tap) {
                    return false;
                }

                $entry_codes[] = $value->code;
                return true;
            })
            ->count();
        $exit_codes = [];
        $exits_count = Tap::query()
            ->whereBetween('tap_datetime', [$start_date, $end_date])
            ->where('location', '=', $location)
            ->where('type', 'exit')
            ->where('status', 'valid')
            ->orderBy('id', 'desc')
            ->with(['guest.booking.room_reservations_no_filter'])
            ->get()
            ->filter(function ($value, $key) use (&$exit_codes, $location, $mode) {
                $checkout = true;

                if ($mode === "2") {
                    $room_reservations = $value["guest"]["booking"]["room_reservations_no_filter"];
                    foreach($room_reservations as $room_reservation) {
                        if ($room_reservation["status"] !== "checked_out") {
                            $checkout = false;
                        }
                    }
                }

                $last_tap = Tap::query()
                    ->where('code', '=', $value->code)
                    ->where('location', '=', $location)
                    ->whereIn('type', ['entry', 'exit'])
                    ->where('status', '=', 'valid')
                    ->orderBy('id', 'desc')
                    ->with(['guest'])
                    ->first();

                $has_new_entry_tap = Tap::query()
                    ->where('tap_datetime', '>', $value->tap_datetime)
                    ->where('code', '=', $value->code)
                    ->where('location', '=', $location)
                    ->where('type', '=', 'entry')
                    ->where('status', '=', 'valid')
                    ->with(['guest'])
                    ->first();

                /*
                if ($mode === "1" && !$checkout) {
                    return false;
                } else
                */

                if ($mode === "1" && in_array($value->code, $exit_codes) || $has_new_entry_tap || $last_tap && $last_tap->type === 'exit') {
                    return false;
                }

                $exit_codes[] = $value->code;
                return true;
            })
            ->count();
        $debug_entry = Tap::query()
            ->whereBetween('tap_datetime', [$start_date, $end_date])
            ->where('location', '=', $location)
            ->where('type', 'entry')
            ->where('status', 'valid')
            ->get();
        $debug_exit = Tap::query()
            ->whereBetween('tap_datetime', [$start_date, $end_date])
            ->where('location', '=', $location)
            ->where('type', 'exit')
            ->where('status', 'valid')
            ->get();
        $used = $entries_count - $exits_count;
        $remaining = $total - $used;
        $has_entry = Tap::query()
            ->whereBetween('tap_datetime', [$start_date, $end_date])
            ->where('location', '=', $location)
            ->where('type', 'entry')
            ->where('status', 'valid')
            ->count();

        $guests = [];
        $guests = Guest::query()
            ->with(['booking'])
            ->whereIn('reference_number', $entry_codes)
            ->get()
            ->filter(function ($value, $key) use ($exit_codes) {
                return !in_array($value->reference_number, $exit_codes);
            });

        return [
            'status' => 'success',
            'data' => [
                'status' => $used < $total ? 'Available' : 'Full',
                'total' => $total,
                'used' => $used,
                'remaining' => $remaining,
                'guests' => $guests,
                'mode' => $mode,
                'has_entry' => $has_entry ? false : false,
            ],
            'entries_count' => $entries_count,
            'exits_count' => $exits_count,
            'entry_codes' => json_encode($entry_codes),
            'exit_codes' => json_encode($exit_codes),
            'debug_entry' => json_encode($debug_entry),
            'debug_exit' => json_encode($debug_exit),
        ];
    }
}
