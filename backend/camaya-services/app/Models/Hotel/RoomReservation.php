<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

use App\Models\Hotel\Room;
// use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomType;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomRate;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RoomReservation extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'room_reservations';
    
    protected $fillable = [
        'room_id',
        'room_type_id',
        'booking_reference_number',
        'category',
        'status',
        'description',
        'allocation_used',
        'start_datetime',
        'end_datetime',
        'check_in_time',
        'checked_in_by',
        'check_out_time',
        'checked_out_by',
        'created_by',
    ];

    protected $casts = [
        'allocation_used' => 'array',
    ];

    public function booking()
    {
        return $this->hasOne('App\Models\Booking\Booking', 'reference_number', 'booking_reference_number');
    }

    public function room_type()
    {
        return $this->hasOne('App\Models\Hotel\RoomType', 'id', 'room_type_id');
    }

    public function room()
    {
        return $this->hasOne('App\Models\Hotel\Room', 'id', 'room_id');
    }

    public function booked_by()
    {
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function checked_in_by_details()
    {
        return $this->hasOne('App\User', 'id', 'checked_in_by');
    }

    public function checked_out_by_details()
    {
        return $this->hasOne('App\User', 'id', 'checked_out_by');
    }

    public static function getAvailableRooms($room_types, $arrival_date, $departure_date)
    {

        $arrival = date('Y-m-d', strtotime($arrival_date))." 12:00:00";
        $departure = date('Y-m-d', strtotime($departure_date))." 11:00:00";
        // Get all the room totals
        $rooms = Room::whereIn('room_type_id', collect($room_types)->pluck('room_type_id')->all())
                    ->select('room_type_id', DB::raw('count(*) as total'))
                    ->where('enabled', 1)
                    ->groupBy('room_type_id')
                    ->get();
        //
        // Get rooms that are booked based on arrival and depature date
        // $booked_rooms = RoomReservation::whereIn('room_type_id',
        //                         collect($room_types)->pluck('room_type_id')->all()
        //                     )
        //                     ->where(function ($query) use ($arrival, $departure) {
        //                         $query->where(function ($query) use ($arrival, $departure) {
        //                             $query->where('start_datetime', '<=', $arrival)
        //                                 ->where('end_datetime', '>=', $arrival);
        //                         })->orWhere(function ($query) use ($arrival, $departure) {
        //                             $query->where('start_datetime', '<=', $departure)
        //                                 ->where('end_datetime', '>=', $departure);
        //                         })->orWhere(function ($query) use ($arrival, $departure) {
        //                             $query->where('start_datetime', '>=', $arrival)
        //                                 ->where('end_datetime', '<=', $departure);
        //                         });
        //                     })
        //                     ->select('room_id', DB::raw('count(*) as booked'))
        //                     ->whereNotNull('room_id')
        //                     ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
        //                     ->groupBy('room_type_id')
        //                     ->get()
        //                     ->toArray();
        
        $booked_rooms = Room::whereIn('id', function ($query) use ($room_types, $arrival, $departure) {
                                            $query->from('room_reservations')
                                            ->whereIn('room_type_id', collect($room_types)->pluck('room_type_id')->all())
                                            ->where(function ($query) use ($arrival, $departure) {
                                                $query->where(function ($query) use ($arrival, $departure) {
                                                    $query->where('start_datetime', '<=', $arrival)
                                                        ->where('end_datetime', '>=', $arrival);
                                                })->orWhere(function ($query) use ($arrival, $departure) {
                                                    $query->where('start_datetime', '<=', $departure)
                                                        ->where('end_datetime', '>=', $departure);
                                                })->orWhere(function ($query) use ($arrival, $departure) {
                                                    $query->where('start_datetime', '>=', $arrival)
                                                        ->where('end_datetime', '<=', $departure);
                                                });
                                            })
                                            ->whereNotNull('room_id')
                                            ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                                            ->select('room_id')
                                            ->pluck('room_id');
                            })
                            ->whereIn('room_type_id',
                                collect($room_types)->pluck('room_type_id')->all()
                            )
                            ->where('enabled', 1)
                            ->select('id as room_id', 'room_type_id')
                            ->get();


         // Get actual available room ids available
        $available_rooms_per_room_type = Room::whereNotIn('id', function ($query) use ($room_types, $arrival, $departure) {
                                            $query->from('room_reservations')
                                            ->whereIn('room_type_id', collect($room_types)->pluck('room_type_id')->all())
                                            ->where(function ($query) use ($arrival, $departure) {
                                                $query->where(function ($query) use ($arrival, $departure) {
                                                    $query->where('start_datetime', '<=', $arrival)
                                                        ->where('end_datetime', '>=', $arrival);
                                                })->orWhere(function ($query) use ($arrival, $departure) {
                                                    $query->where('start_datetime', '<=', $departure)
                                                        ->where('end_datetime', '>=', $departure);
                                                })->orWhere(function ($query) use ($arrival, $departure) {
                                                    $query->where('start_datetime', '>=', $arrival)
                                                        ->where('end_datetime', '<=', $departure);
                                                });
                                            })
                                            ->whereNotNull('room_id')
                                            ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                                            ->select('room_id')
                                            ->pluck('room_id');
                            })
                            ->whereIn('room_type_id',
                                collect($room_types)->pluck('room_type_id')->all()
                            )
                            ->where('enabled', 1)
                            ->select('id as room_id', 'room_type_id')
                            ->get();

        $booked = [];

        foreach ($room_types as $room_type) {

            // $room = collect($booked_rooms)->firstWhere('room_type_id', $room_type['room_type_id']);
            $room = collect($booked_rooms)->where('room_type_id', $room_type['room_type_id']);

            $booked[] = [
                'room_type_id' => $room_type['room_type_id'],
                // 'booked' => $room['booked'] ?? 0,
                'booked' => count($room) ?? 0,
            ];
        }

        $booked_room_ids = [];

        foreach (collect($available_rooms_per_room_type)->groupBy('room_type_id')->toArray() as $room_type_id => $room_type) {
            $room_ids = [];

            foreach ($room_type as $room) {
                $room_ids[] = $room['room_id'];
            }

            $booked_room_ids[] = [
                'room_type_id' => $room_type_id,
                'available_room_ids' => $room_ids,
            ];
        }

        // return $booked_room_ids;
        return array_replace_recursive($room_types, $rooms->toArray(), $booked, $booked_room_ids);

        // return [
        //     // 'arrival' => $arrival,
        //     // 'departure' => $departure,
        //     'room_types' => $available_rooms_per_room_type,
        //     'room_totals' => $rooms,
        //     'booked_rooms' => $booked_room_ids,
        //     'merge' => array_replace_recursive($room_types, $rooms->toArray(), $booked_rooms->toArray()),
        // ];
    }


    public static function roomAllocationForBooking($request)
    {
        $role = $request['user']['roles'][0]['name'] ?? null;
        // return $request;
        $arrival = $request['arrival']." 12:00:00";
        $departure = $request['departure']." 11:00:00";

        $period = CarbonPeriod::create($request['arrival'], Carbon::parse($request['departure'])->subDays(1));

        $dates = collect($period)->map(function ($item, $key) {
            return $item->isoFormat('YYYY-MM-DD');
        })->all();

        $room_allocations_ = RoomAllocation::where( function ($q) use ($request) {
            $q->where('date', '>=', $request['arrival']);
            $q->where('date', '<', $request['departure']);
        })
                ->where('status', 'approved')
                ->select('allowed_roles','allocation', 'date', 'entity', 'id', 'room_type_id', 'status', 'used')
                // ->selectRaw("GREATEST(allocation-used, 0) as available")
                // ->selectRaw("IF((allocation - used) < 0, 0,(allocation - used)) as available")
                ->get();
                
        $room_allocations = [];
                
        foreach ($room_allocations_ as $item) {
            $room_allocations[] = [
                'id' => $item['id'],
                'allowed_roles' => $item['allowed_roles'],
                'allocation' => $item['allocation'],
                'date' => $item['date'],
                'entity' => $item['entity'],
                'room_type_id' => $item['room_type_id'],
                'status' => $item['status'],
                'used' => $item['used'],
                
                'available' => max($item['allocation']-$item['used'], 0)
            ];
        }

        $room_types_with_allocations = [];
        $entities = ['BPO', 
                    'HOA', 
                    'RE', 
                    'OTA',
                    'SD Rudolph Cortez',
                    'SD Louie Paule',
                    'SD Luz Dizon',
                    'SD John Rizaldy Zuno',
                    'SD Brian Beltran',
                    'SD Jake Tuazon',
                    'SD Joey Bayon',
                    'SD Grace Laxa',
                    'SD Stephen Balbin',
                    'SD Maripaul Milanes',
                    'SD Danny Ngoho',
                    'SD Harry Colo',
                    'SD Lhot Quiambao'
                    ]; // Update this to a pivot table or something manageable

        foreach ($entities as $entity) {
            foreach (collect($room_allocations)->groupBy('room_type_id')->toArray() as $room_type_id => $items) {

                $available_rooms_per_room_type = Room::whereNotIn('id', function ($query) use ($room_type_id, $arrival, $departure) {
                                                                $query->from('room_reservations')
                                                                    ->whereIn('room_type_id', [$room_type_id]
                                                                )
                                                                ->where(function ($query) use ($arrival, $departure) {
                                                                    $query->where(function ($query) use ($arrival, $departure) {
                                                                        $query->where('start_datetime', '<=', $arrival)
                                                                            ->where('end_datetime', '>=', $arrival);
                                                                    })->orWhere(function ($query) use ($arrival, $departure) {
                                                                        $query->where('start_datetime', '<=', $departure)
                                                                            ->where('end_datetime', '>=', $departure);
                                                                    })->orWhere(function ($query) use ($arrival, $departure) {
                                                                        $query->where('start_datetime', '>=', $arrival)
                                                                            ->where('end_datetime', '<', $departure);
                                                                    });
                                                                })
                                                                ->whereNotNull('room_id')
                                                                ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                                                                ->select('room_id')
                                                                ->pluck('room_id');
                                                    })
                                                    ->whereIn('room_type_id',
                                                        [$room_type_id]
                                                    )
                                                    ->where('enabled', 1)
                                                    ->select('id as room_id', 'room_type_id')
                                                    ->get();

                /**
                 * Get booked/blackout rooms per room type
                 */

                // $room_bookings = RoomReservation::where(function ($query) use ($arrival, $departure) {
                //                         $query->where(function ($query) use ($arrival, $departure) {
                //                             $query->where('start_datetime', '<=', $arrival)
                //                                 ->where('end_datetime', '>=', $arrival);
                //                         })->orWhere(function ($query) use ($arrival, $departure) {
                //                             $query->where('start_datetime', '<=', $departure)
                //                                 ->where('end_datetime', '>=', $departure);
                //                         })->orWhere(function ($query) use ($arrival, $departure) {
                //                             $query->where('start_datetime', '>=', $arrival)
                //                                 ->where('end_datetime', '<', $departure);
                //                         });
                //                     })
                //                     ->where('room_type_id', $room_type_id)
                //                     ->where('status', 'blackout')
                //                     ->whereHas('room', function ($q) {
                //                         $q->where('enabled', 1);
                //                     })
                //                     ->get();

                $room_allocation_dates = collect($items)->where('entity', $entity)
                                            ->map(function ($item, $key) use ($entity) {
                                                return date('Y-m-d', strtotime($item['date']));
                                            })->all();

                $diff = collect($dates)->diff($room_allocation_dates);

                $room_type = RoomType::where('id', $room_type_id)->withCount('enabledRooms')->with('images')->with('property')->first();

                $room_rate_total = 0;
                foreach ($dates as $date) {

                    /**
                     * Room rate
                     */
                    $room_rate = RoomRate::where('room_type_id', $room_type_id)
                                            ->whereDate('start_datetime', '<=', $date)
                                            ->whereDate('end_datetime', '>=', $date)
                                            ->whereRaw('json_contains(days_interval, \'["'. strtolower(Carbon::parse($date)->isoFormat('ddd')) .'"]\')')
                                            ->whereRaw('json_contains(allowed_roles, \'["'. $role .'"]\')')
                                            // ->whereRaw('json_contains(allowed_roles, \'["customer"]\')')
                                            ->orderBy('created_at', 'desc')
                                            ->where('status', 'approved')
                                            ->first();

                    if ($room_rate) {
                        $isDayAllowed = in_array(strtolower(Carbon::parse($date)->isoFormat('ddd')), $room_rate->days_interval);
                        $isDayExcluded = in_array($date, $room_rate->exclude_days);
                    }

                    $selling_rate = $room_type->rack_rate;

                    if ($room_rate && $isDayAllowed == true && $isDayExcluded == false) {
                        $selling_rate = $room_rate->room_rate;
                    }

                    $room_rate_total = $room_rate_total + $selling_rate;
                    /**
                     * End of room rate
                     */

                }

                // $available_rooms = count($diff->all()) == 0 ? (collect($items)->where('entity', $entity)->min('available')) : 0;
                $isEntityAvailableHigherThanActualAvailableRooms = (collect($items)->where('entity', $entity)->min('available')) < count($available_rooms_per_room_type);

                $available_rooms = 0;

                if (count($diff->all()) == 0) {
                    
                    if ((collect($items)->where('entity', $entity)->min('available')) < count($available_rooms_per_room_type)) {
                        $available_rooms = (collect($items)->where('entity', $entity)->min('available'));
                    } else {
                        $available_rooms = count($available_rooms_per_room_type);
                    }

                }

                $room_types_with_allocations[] = [
                    'room_type_id' => $room_type_id,
                    'room_type' => $room_type,
                    'entity' => $entity,
                    'isAvailable' => count($diff->all()) == 0 ? true : false,
                    'room_allocations' => collect($items)->where('entity', $entity),
                    'available' => $available_rooms,
                    // 'sum1' => collect($items)->sum('allocation'), // 30
                    // 'sum2' => collect($items)->where('entity', $entity)->sum('allocation'), // 12
                    // 'available_with_room_blockings' => (collect($items)->sum('allocation') - count($room_blockings)) - collect($items)->where('entity', $entity)->sum('allocation'),
                    'room_rate_total' => $room_rate_total,
                    'available_rooms_per_room_type' => ($available_rooms_per_room_type),
                    // 'room_blockings_data' => ($room_blockings),
                    // 'test' => $room_allocations,
                    //'room_rate_total' => $room_type_not_in_list->rack_rate * count($period),
                ];
            }
        }

        return $room_types_with_allocations;
    }

}
