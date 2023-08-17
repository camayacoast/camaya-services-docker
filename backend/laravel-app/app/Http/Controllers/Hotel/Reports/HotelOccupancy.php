<?php

namespace App\Http\Controllers\Hotel\Reports;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\Hotel\Property;
use App\Models\Hotel\Room;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class HotelOccupancy extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($start_date, $end_date, $download = false)
    {
        if (env('APP_ENV') === 'production') {
            return false;
        }

        $week_number_name = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
        $data = [];
        $room_allocations = RoomAllocation::query()
            ->orWhere(function($query) use ($start_date, $end_date) {
                $query
                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date);
            })
            ->with(['room_type'])
            ->get();

        $room_reservations = RoomReservation::query()
            ->orWhere(function($query) use ($start_date, $end_date) {
                $query
                    ->whereDate('start_datetime', '>=', $start_date)
                    ->whereDate('start_datetime', '<=', $end_date);
            })
            ->orWhere(function($query) use ($start_date, $end_date) {
                $query
                    ->whereDate('end_datetime', '>=', $start_date)
                    ->whereDate('end_datetime', '<=', $end_date);
            })
            ->with(['room.property', 'room_type'])
            ->get();
        // Log::debug($room_reservations);

        $date_data = [];
        $room_type_capacity_per_date = [];                

        if ($download) {
            $start_week = Carbon::create($start_date)->startOfWeek();
            $start_week_date = Carbon::create($start_date)->startOfDay();
            $start_week_period = CarbonPeriod::create($start_week, $start_week_date);

            // $end_week = Carbon::create($end_date)->endOfWeek();
            // $end_date_week = Carbon::create($end_date);
            // $end_week_period = CarbonPeriod::create($end_week, $end_week_date);

            if (!$start_week->equalTo($start_week_date)) {
                foreach($start_week_period as $date) {
                    if ($start_week_date->format('w') !== $date->format('w')) {
                        $date_data[$date->format('Y-m-d')] = [
                            'blank' => true,
                            'day' => $date->format('j'),
                            'month' => $date->format('F'),
                            'date' => $date->format('Y-m-d'),
                            'week_number' => $date->format('w'),
                            'week_number_name' => $week_number_name[$date->format('w')],
                        ];
                    }
                }
            }
        }

        $period = CarbonPeriod::create($start_date, $end_date);
        foreach ($period as $date) {
            // $data[$date->format('d')] = [];

            $room_type_allocation = [
                'Deluxe Twin' => 0,
                'Deluxe Queen' => 0,
                'Family Suite' => 0,
                'Executive Suite' => 0,
            ];
    
            foreach($room_allocations as $room_allocation) {
                $room_allocation_date = Carbon::create($room_allocation->date)->format('Y-m-d');
                if ($room_allocation_date === $date->format('Y-m-d')) {
                    try {
                        $room_type_allocation[$room_allocation->room_type->name] += $room_allocation->allocation;                        
                    } catch (\Throwable $th) {
                        //do nothing
                    }
                }
            }
    
            $room_type_capacity_per_date[$date->format('Y-m-d')] = $room_type_allocation;

            $has_room_reservation = false;
            foreach($room_reservations as $room_reservation) {
                $start_datetime = Carbon::create($room_reservation->start_datetime)->startOfDay();
                $end_datetime = Carbon::create($room_reservation->end_datetime)->subDay()->endOfDay();                

                if ($date->between($start_datetime, $end_datetime) && $room_reservation['allocation_used'] && $room_reservation['room']['room_status'] !== 'out-of-order') {
                    $has_room_reservation = true;
                    $entity = RoomAllocation::find($room_reservation['allocation_used'])->pluck('entity')->all();

                    $room_type_allocation = 0;
                    foreach($room_allocations as $room_allocation) {
                        if (in_array($room_allocation->id, $room_reservation['allocation_used'])) {
                            $room_type_allocation = $room_allocation->allocation;
                        }
                    }

                    $date_data[$date->format('Y-m-d')][] = [
                        'day' => $date->format('j'),
                        'month' => $date->format('F'),
                        'date' => $date->format('Y-m-d'),
                        'week_number' => $date->format('w'),
                        'week_number_name' => $week_number_name[$date->format('w')],
                        'room_type_name' => $room_reservation->room_type->name,
                        'room_type_code' => $room_reservation->room_type->code,
                        'room_type_capacity' => $room_reservation->room_type->capacity,
                        'room_type_allocation' => $room_type_allocation,
                        'hotel' => $room_reservation->room->property->name,
                        'hotel_code' => $room_reservation->room->property->code,
                        'status' => $room_reservation->status,
                        'room_reservation' => json_encode($room_reservation),
                        'entity' => $entity,
                    ];
                }
            }

            if (!$has_room_reservation) {
                $date_data[$date->format('Y-m-d')] = [
                    'no_data' => true,
                    'day' => $date->format('j'),
                    'month' => $date->format('F'),
                    'date' => $date->format('Y-m-d'),
                    'week_number' => $date->format('w'),
                    'week_number_name' => $week_number_name[$date->format('w')],
                ];
            }
        }

        if ($download) {
            // $start_week = Carbon::create($start_date)->startOfWeek();
            // $start_week_date = Carbon::create($start_date)->startOfDay();
            // $start_week_period = CarbonPeriod::create($start_week, $start_week_date);

            $end_week = Carbon::create($end_date)->endOfWeek();
            $end_week_date = Carbon::create($end_date)->startOfDay();
            $end_week_period = CarbonPeriod::create($end_week_date, $end_week);

            if (!$end_week_date->equalTo($end_week)) {
                foreach($end_week_period as $date) {
                    if ($end_week_date->format('w') !== $date->format('w')) {
                        $date_data[$date->format('Y-m-d')] = [
                            'blank' => true,
                            'day' => $date->format('j'),
                            'month' => $date->format('F'),
                            'date' => $date->format('Y-m-d'),
                            'week_number' => $date->format('w'),
                            'week_number_name' => $week_number_name[$date->format('w')],
                        ];
                    }
                }
            }
        }

        // Log::debug($date_data);
        // Log::debug($room_type_capacity_per_date);

        foreach($date_data as $date_data_key=>$date_data_values) {
            $data[$date_data_key] = [
                'id' => $date_data_key,
                // 'date_month' => $date_data_values['month'],
                // 'date_number' => $date_data_key,
                // 'date' => $date_data_values['date'],
                'deluxe_twin_bpo' => 0,
                'deluxe_twin_re' => 0,
                'deluxe_twin_hoa' => 0,
                'deluxe_twin_foc' => 0,
                'deluxe_twin_walk_in' => 0,
                'deluxe_twin_occupancy' => 0,
                'deluxe_twin_percent' => '',
                // 'deluxe_twin_room_total' => 0,
                'deluxe_twin_room_total' => $room_type_capacity_per_date[$date_data_key]['Deluxe Twin'],
                'family_suite_bpo' => 0,
                'family_suite_re' => 0,
                'family_suite_hoa' => 0,
                'family_suite_foc' => 0,
                'family_suite_walk_in' => 0,
                'family_suite_occupancy' => 0,
                'family_suite_percent' => '',
                // 'family_suite_room_total' => 0,
                'family_suite_room_total' => $room_type_capacity_per_date[$date_data_key]['Family Suite'],
                'deluxe_queen_bpo' => 0,
                'deluxe_queen_re' => 0,
                'deluxe_queen_hoa' => 0,
                'deluxe_queen_foc' => 0,
                'deluxe_queen_walk_in' => 0,
                'deluxe_queen_occupancy' => 0,
                'deluxe_queen_percent' => '',
                // 'deluxe_queen_room_total' => 0,
                'deluxe_queen_room_total' => $room_type_capacity_per_date[$date_data_key]['Deluxe Queen'],
                'executive_suite_bpo' => 0,
                'executive_suite_re' => 0,
                'executive_suite_hoa' => 0,
                'executive_suite_foc' => 0,
                'executive_suite_walk_in' => 0,
                'executive_suite_occupancy' => 0,
                'executive_suite_percent' => '',
                // 'executive_suite_room_total' => 0,
                'executive_suite_room_total' => $room_type_capacity_per_date[$date_data_key]['Executive Suite'],
                'total_bpo' => 0,
                'total_re' => 0,
                'total_hoa' => 0,
                'total_foc' => 0,
                'total_walk_in' => 0,
                'total_occupancy' => 0,
                'total_percent' => '0 %',
                // 'total_rooms' => 0,
                'total_rooms' => $room_type_capacity_per_date[$date_data_key]['Deluxe Twin'] + $room_type_capacity_per_date[$date_data_key]['Family Suite'] + $room_type_capacity_per_date[$date_data_key]['Deluxe Queen'] + $room_type_capacity_per_date[$date_data_key]['Executive Suite'],
            ];

            if (array_key_exists('blank', $date_data_values)) {
                $data[$date_data_key]['blank'] = true;
                $data[$date_data_key]['date_month'] = $date_data_values['month'];
                $data[$date_data_key]['date_number'] = $date_data_values['day'];
                $data[$date_data_key]['date'] = $date_data_values['date'];
                $data[$date_data_key]['date_week_number'] = $date_data_values['week_number'];
                $data[$date_data_key]['date_week_number_name'] = $date_data_values['week_number_name'] ?? 7;
            } else if (array_key_exists('no_data', $date_data_values)) {
                $data[$date_data_key]['date_month'] = $date_data_values['month'];
                $data[$date_data_key]['date_number'] = $date_data_values['day'];
                $data[$date_data_key]['date'] = $date_data_values['date'];
                $data[$date_data_key]['date_week_number'] = $date_data_values['week_number'];
                $data[$date_data_key]['date_week_number_name'] = $date_data_values['week_number_name'] ?? 7;
            } else {
                foreach($date_data_values as $date_data_value) {
                    $data[$date_data_key]['date_month'] = $date_data_value['month'];
                    $data[$date_data_key]['date_number'] = $date_data_value['day'];
                    $data[$date_data_key]['date'] = $date_data_value['date'];
                    $data[$date_data_key]['date_week_number'] = $date_data_value['week_number'];
                    $data[$date_data_key]['date_week_number_name'] = $date_data_value['week_number_name'] ?? 7;

                    switch ($date_data_value['room_type_name']) {
                        case 'Deluxe Twin':
                            if (in_array('BPO', $date_data_value['entity'])) {
                                $data[$date_data_key]['deluxe_twin_bpo']++;
                                $data[$date_data_key]['total_bpo']++;
                            }

                            if (in_array('RE', $date_data_value['entity'])) {
                                $data[$date_data_key]['deluxe_twin_re']++;
                                $data[$date_data_key]['total_re']++;
                            }

                            if (in_array('HOA', $date_data_value['entity'])) {
                                $data[$date_data_key]['deluxe_twin_hoa']++;
                                $data[$date_data_key]['total_hoa']++;
                            }

                            $data[$date_data_key]['deluxe_twin_occupancy']++;
                            $data[$date_data_key]['total_occupancy']++;

                            // if (!$data[$date_data_key]['deluxe_twin_room_total']) {
                            //     $data[$date_data_key]['deluxe_twin_room_total']+=$date_data_value['room_type_allocation'];
                            //     $data[$date_data_key]['total_rooms']+=$date_data_value['room_type_allocation'];
                            // }
                            break;

                        case 'Family Suite':
                            if (in_array('BPO', $date_data_value['entity'])) {
                                $data[$date_data_key]['family_suite_bpo']++;
                                $data[$date_data_key]['total_bpo']++;
                            }

                            if (in_array('RE', $date_data_value['entity'])) {
                                $data[$date_data_key]['family_suite_re']++;
                                $data[$date_data_key]['total_re']++;
                            }

                            if (in_array('HOA', $date_data_value['entity'])) {
                                $data[$date_data_key]['family_suite_hoa']++;
                                $data[$date_data_key]['total_hoa']++;
                            }

                            $data[$date_data_key]['family_suite_occupancy']++;
                            $data[$date_data_key]['total_occupancy']++;

                            // if (!$data[$date_data_key]['family_suite_room_total']) {
                            //     $data[$date_data_key]['family_suite_room_total']+=$date_data_value['room_type_allocation'];
                            //     $data[$date_data_key]['total_rooms']+=$date_data_value['room_type_allocation'];
                            // }
                            break;

                        case 'Deluxe Queen':
                            if (in_array('BPO', $date_data_value['entity'])) {
                                $data[$date_data_key]['deluxe_queen_bpo']++;
                                $data[$date_data_key]['total_bpo']++;
                            }

                            if (in_array('RE', $date_data_value['entity'])) {
                                $data[$date_data_key]['deluxe_queen_re']++;
                                $data[$date_data_key]['total_re']++;
                            }

                            if (in_array('HOA', $date_data_value['entity'])) {
                                $data[$date_data_key]['deluxe_queen_hoa']++;
                                $data[$date_data_key]['total_hoa']++;
                            }

                            $data[$date_data_key]['deluxe_queen_occupancy']++;
                            $data[$date_data_key]['total_occupancy']++;

                            // if (!$data[$date_data_key]['deluxe_queen_room_total']) {
                            //     $data[$date_data_key]['deluxe_queen_room_total']+=$date_data_value['room_type_allocation'];
                            //     $data[$date_data_key]['total_rooms']+=$date_data_value['room_type_allocation'];
                            // }
                            break;

                        case 'Executive Suite':
                            if (in_array('BPO', $date_data_value['entity'])) {
                                $data[$date_data_key]['executive_suite_bpo']++;
                                $data[$date_data_key]['total_bpo']++;
                            }

                            if (in_array('RE', $date_data_value['entity'])) {
                                $data[$date_data_key]['executive_suite_re']++;
                                $data[$date_data_key]['total_re']++;
                            }

                            if (in_array('HOA', $date_data_value['entity'])) {
                                $data[$date_data_key]['executive_suite_hoa']++;
                                $data[$date_data_key]['total_hoa']++;
                            }

                            $data[$date_data_key]['executive_suite_occupancy']++;
                            $data[$date_data_key]['total_occupancy']++;

                            // if (!$data[$date_data_key]['executive_suite_room_total']) {
                            //     $data[$date_data_key]['executive_suite_room_total']+=$date_data_value['room_type_allocation'];
                            //     $data[$date_data_key]['total_rooms']+=$date_data_value['room_type_allocation'];
                            // }
                            break;

                        default:
                            break;
                    }                    
                }

                try {
                    $total_percent = (int) (($data[$date_data_key]['total_occupancy'] / $data[$date_data_key]['total_rooms']) * 100);
                    $data[$date_data_key]['total_percent'] = "$total_percent %";
                } catch (\Throwable $th) {
                    //do nothing
                }

            }
        }

        // Log::debug($data);

        if (!$start_date || !$end_date) {
            return response()->json([
                'status' => true,
                'data' => [],
            ]);
        }

        if ($download) {
            return Excel::download(
                new ReportExport('reports.hotel.hotel-occupancy', $data),
                'report.xlsx'
            );
        }

        return response()->json([
            'status' => true,
            'data' => array_values($data,)
        ]);
    }
}
