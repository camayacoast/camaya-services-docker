<?php

namespace App\Http\Controllers\AutoGate;

use App\Http\Controllers\Controller;
use App\Models\AutoGate\Pass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GateSyncAF extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // Log::debug($request);
        // return response()->json([
        //     'success' => true,
        //     'data' => [
        //         'passes' => [],
        //         'taps' => [],
        //     ],
        // ]);

        $query = Pass::query()
            ->select(
            'passes.guest_reference_number',
            'passes.booking_reference_number',
            'mode',
            'interfaces',
            'category',
            'usable_at',
            'expires_at',
            'passes.status as pass_status',
            'guests.status as guest_status',
            'bookings.status as booking_status',
            'passes.updated_at as pass_updated_at',
            'guests.updated_at as guest_updated_at',
            'bookings.updated_at as booking_updated_at',
            )
            ->join('bookings', 'booking_reference_number', 'bookings.reference_number')
            ->join('guests', 'guest_reference_number', 'guests.reference_number');
        
        if ($request->guest_updated_at && $request->booking_updated_at && $request->passes_updated_at) {
            $query
                ->where('passes.updated_at', '>', $request->pass_updated_at)
                ->where('bookings.updated_at', '>', $request->booking_updated_at)
                ->orWhere('guests.updated_at', '>', $request->guest_updated_at);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'passes' => $query->get(),
                'taps' => $this->create('taps', $request->taps_insert_data),
            ],
        ]);
    }

    private function create($table, $insert_data)
    {
        return collect($insert_data)->map(function ($item) use ($table) {
            $query = DB::connection('camaya_booking_db')->table($table);
            $local_tap_id = $item['id'];
            $item_object_to_array_collection = collect($item)
                ->except(['id'])
                ->map(function ($item, $key) {
                    if ($key === 'created_at') return Carbon::parse($item);
                    else if ($key === 'updated_at') return Carbon::now();
                    return $item;
                })
                ->toArray();

            return [
                'local_id' => $local_tap_id,
                'cloud_id' => $query->insertGetId($item_object_to_array_collection)
            ];
        });
    }
}
