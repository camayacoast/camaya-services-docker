<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Models\Booking\DailyGuestLimit;

class GenerateDailyGuestPerDay extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        // return $request->all();

        if (!$request->daily_guest_limits) {
            return response()->json(['error' => 'NO_DAILY_GUEST_LIMIT', 'message' => 'Add atleast 1 allocation.'], 400);
        }

        /**
         * Set start and end date with timezone
         */
        $start = Carbon::parse(date('Y-m-d', strtotime($request->range[0])))->setTimezone('Asia/Manila')->isoFormat('YYYY-MM-DD');
        $end = Carbon::parse(date('Y-m-d', strtotime($request->range[1])))->setTimezone('Asia/Manila')->isoFormat('YYYY-MM-DD');

        $period = CarbonPeriod::create($start, $end);

        $daily_guest_limit_to_save = [];

        $to_skip = [];
        $skip_id = 1;

        $data = [];

        foreach ($period as $date) {

            $formattedDate = $date->isoFormat('YYYY-MM-DD');

            foreach ($request->daily_guest_limits as $daily_guest_limit) {

                $daily_guest_limit['date'] = $formattedDate;

                $day = strtolower(Carbon::parse($formattedDate)->isoFormat('dddd'));
                $limit = (isset($daily_guest_limit[$day]) && $daily_guest_limit[$day] >= 0) ? $daily_guest_limit[$day] : null;

                /**
                 * Check if date has daily limit already
                 * by date 
                 */
                $checkIfDateHasLimit = DailyGuestLimit::where('date', $formattedDate)
                    ->where('category', $daily_guest_limit['category'])
                    ->first();

                if ($checkIfDateHasLimit && ($limit && $checkIfDateHasLimit['category'] == $daily_guest_limit['category']) ) {
                    $to_skip[] = [
                        'id' => $skip_id,
                        'date' => $formattedDate,
                        'category' => $daily_guest_limit['category'],
                        'limit' => $checkIfDateHasLimit['limit'],
                    ];

                    $skip_id++;
                } else {
                    if ($limit) {
                        $data[] = [
                            'date' => $formattedDate,
                            // 'day' => $day,
                            'category' => $daily_guest_limit['category'],
                            'limit' => $limit,
                            'status' => 'approved', // Change this if feature is needed
                            'created_by' => $request->user()->id,
                            'created_at' => now(),
                        ];
                    }
                }

            }
            
        }

        if (!$data && !$to_skip) return response()->json(['message' => 'No record saved.'], 400);

        // Save to model
        DailyGuestLimit::insert($data);

        return response()->json(['skipped' => $to_skip, 'saved_data' => $data], 200);
    }
}
