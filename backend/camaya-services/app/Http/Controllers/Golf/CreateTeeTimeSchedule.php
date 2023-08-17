<?php

namespace App\Http\Controllers\Golf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Golf\TeeTimeSchedule;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CreateTeeTimeSchedule extends Controller
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

        $start_date = Carbon::parse($request->date_range[0])->setTimeZone('Asia/Manila')->format('Y-m-d');
        $end_date = Carbon::parse($request->date_range[1])->setTimeZone('Asia/Manila')->format('Y-m-d');

        $time = Carbon::parse($request->time)->setTimeZone('Asia/Manila')->format('H:i:s');

        $period = CarbonPeriod::create($start_date, $end_date);

        $existingSchedules = [];

        $schedules_to_save = [];

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        

            foreach ($request->entity as $entity) {

                $exist = TeeTimeSchedule::whereTime('time', $time)
                                        ->where('entity', $entity)
                                        ->whereIn('mode_of_transportation', ['all', $request->mode_of_transportation])
                                        ->whereDate('date', $date->format('Y-m-d'))->first();

                if ($exist) {

                    $existingSchedules[] = $exist;

                } else {

                    $schedules_to_save[] = [
                        'date' => $date,
                        'time' => $time,
                        'entity' => $entity,
                        'allocation' => $request->allocation,
                        'mode_of_transportation' => $request->mode_of_transportation,
                        'status' => $request->save_as_approved == 'yes' ? 'approved' : 'for_review',
                        'created_by' => $request->user()->id,
                        'created_at' => Carbon::now()->setTimeZone('Asia/Manila'),
                    ];

                }
                
            } // End entity foreach

        } // End period foreach

        /**
         * Save all schedules
         */
        TeeTimeSchedule::insert($schedules_to_save);

        return response()->json(['existing_schedules' => $existingSchedules, 'saved_records' => count($schedules_to_save)], 200);
    }
}
