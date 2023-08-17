<?php

namespace App\Http\Controllers\AutoGate;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Database\Eloquent\Builder;

class GateSync extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $decoded_filter_for_inserts_updates = json_decode($request->filter_for_inserts_updates);

        $bookings = $this->read('bookings', $decoded_filter_for_inserts_updates->bookings->id, $decoded_filter_for_inserts_updates->bookings->updated_at, []);

        $booking_refnos = collect($bookings)->pluck('reference_number')->all();
        
        return response()->json([
            'success' => true,
            'data' => [
                'bookings' => $bookings,
                'booking_tags' => $this->read('booking_tags', $decoded_filter_for_inserts_updates->booking_tags->id, $decoded_filter_for_inserts_updates->booking_tags->updated_at, $booking_refnos),
                'customers' => $this->read('customers', $decoded_filter_for_inserts_updates->customers->id, $decoded_filter_for_inserts_updates->customers->updated_at, []),
                'guest_updates' => $this->update_guests('guests', $decoded_filter_for_inserts_updates->guests->update_data),
                'guests' => $this->read('guests', $decoded_filter_for_inserts_updates->guests->id, $decoded_filter_for_inserts_updates->guests->updated_at, $booking_refnos),
                'guest_vehicle_inserts' => $this->create('guest_vehicles', $decoded_filter_for_inserts_updates->guest_vehicles->insert_data, []),
                'guest_vehicle_updates' => $this->update('guest_vehicles', $decoded_filter_for_inserts_updates->guest_vehicles->update_data),
                'guest_vehicles' => $this->read('guest_vehicles', $decoded_filter_for_inserts_updates->guest_vehicles->id, $decoded_filter_for_inserts_updates->guest_vehicles->updated_at, $booking_refnos),
                'inclusions' => $this->read('inclusions', $decoded_filter_for_inserts_updates->inclusions->id, $decoded_filter_for_inserts_updates->inclusions->updated_at, $booking_refnos),
                'invoices' => $this->read('invoices', $decoded_filter_for_inserts_updates->invoices->id, $decoded_filter_for_inserts_updates->invoices->updated_at, $booking_refnos),
                'pass_updates' => $this->update('passes', $decoded_filter_for_inserts_updates->passes->update_data),
                'passes' => $this->read('passes', $decoded_filter_for_inserts_updates->passes->id, $decoded_filter_for_inserts_updates->passes->updated_at, $booking_refnos),
                'taps' => $this->createTaps('taps', $decoded_filter_for_inserts_updates->taps->insert_data),
            ]
        ]);
    }

    private function read($table, $id, $updated_at, $booking_refnos)
    {
        $query = DB::connection('camaya_booking_db')->table($table);

        if ($id && $updated_at) {
            $query->where($table.'.id', '>', $id);
            $query->orWhere($table.'.updated_at', '>', $updated_at);
        }

        if ($id && ! $updated_at) {
            $query->where($table.'.id', '>', $id);
        }

        if (! $id && $updated_at) {
            $query->where($table.'.updated_at', '>', $updated_at);
        }

        if (in_array($table, ['bookings', 'guests', 'inclusions', 'invoices', 'passes', 'guest_vehicles'])) {
            if ($table == 'bookings') {
                $query->whereDate('start_datetime', '>', now()->subDays(1)->toDateString());
                $query->whereDate('start_datetime', '<', now()->addDays(2)->toDateString());
            } else if ($table == 'guests') {
                $query->leftJoin('bookings', 'guests.booking_reference_number', '=', 'bookings.reference_number');
                $query->whereDate('bookings.start_datetime', '>', now()->subDays(1)->toDateString());
                $query->whereDate('bookings.start_datetime', '<', now()->addDays(2)->toDateString());
                $query->select('guests.*');
            } else if ($table == 'inclusions') {
                $query->leftJoin('bookings', 'inclusions.booking_reference_number', '=', 'bookings.reference_number');
                $query->whereDate('bookings.start_datetime', '>', now()->subDays(1)->toDateString());
                $query->whereDate('bookings.start_datetime', '<', now()->addDays(2)->toDateString());
                $query->select('inclusions.*');
            } else if ($table == 'invoices') {
                $query->leftJoin('bookings', 'invoices.booking_reference_number', '=', 'bookings.reference_number');
                $query->whereDate('bookings.start_datetime', '>', now()->subDays(1)->toDateString());
                $query->whereDate('bookings.start_datetime', '<', now()->addDays(2)->toDateString());
                $query->select('invoices.*');
            } else if ($table == 'passes') {
                $query->leftJoin('bookings', 'passes.booking_reference_number', '=', 'bookings.reference_number');
                $query->whereDate('bookings.start_datetime', '>', now()->subDays(1)->toDateString());
                $query->whereDate('bookings.start_datetime', '<', now()->addDays(2)->toDateString());
                $query->select('passes.*');
            } else if ($table == 'guest_vehicles') {
                $query->leftJoin('bookings', 'guest_vehicles.booking_reference_number', '=', 'bookings.reference_number');
                $query->whereDate('bookings.start_datetime', '>', now()->subDays(1)->toDateString());
                $query->whereDate('bookings.start_datetime', '<', now()->addDays(2)->toDateString());
                $query->select('guest_vehicles.*');
            }
        }

        // $data = [];

        return $query->get();
        
        // $query->orderBy($table.'.id', 'ASC')->chunk(100, function ($items) use (&$data) {
        //     // $data = $data + $items;
        //     foreach ($items as $item) {
        //         $data[] = $item;
        //     }
        // });

        // return $data;
    }

    private function create($table, $insert_data)
    {
        return collect($insert_data)->map(function ($item) use ($table) {
            $query = DB::connection('camaya_booking_db')->table($table);
            $local_tap_id = $item->id;
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

    private function createTaps($table, $insert_data)
    {

        return [];
        
        return collect($insert_data)->map(function ($item) use ($table) {
            $query = DB::connection('camaya_booking_db')->table($table);
            $local_tap_id = $item->id;

            // Check if already exists
            // $exists = $query->where('code', $item->code)
            //                 ->where('tap_datetime', $item->tap_datetime)
            //                 ->where('location', $item->location)
            //                 ->first();

            // if (!$exists) {
            //     $item_object_to_array_collection = collect($item)
            //     ->except(['id'])
            //     ->map(function ($item, $key) {
            //         if ($key === 'created_at') return Carbon::parse($item);
            //         else if ($key === 'updated_at') return Carbon::now();
            //         return $item;
            //     })
            //     ->toArray();
            // }
            
            $save = $query->updateOrCreate(
                ['code' => $item->code, 'tap_datetime' => $item->tap_datetime, 'location' => $item->location],
                ['created_at' => $item->tap_datetime, 'updated_at' => Carbon::now()]
            );

            return [
                'local_id' => $local_tap_id,
                'cloud_id' => $save
            ];
        });
    }

    private function update($table, $update_data)
    {
        try {
            DB::beginTransaction();

            $updated_data = collect($update_data)->map(function ($item) use ($table) {
                $item_object_to_array_collection_to_update = collect($item)
                    ->except(['id'])
                    ->map(function ($item, $key) {
                        if ($key === 'created_at') return Carbon::parse($item);
                        else if ($key === 'updated_at') return Carbon::now();
                        return $item;
                    })
                    ->toArray();

                DB::connection('camaya_booking_db')
                    ->table($table)
                    ->where('id', $item->id)
                    ->update($item_object_to_array_collection_to_update);

                return [
                    'local_id' => $item->id,
                    'cloud_id' => $item->id
                ];
            });

            DB::commit();
            return $updated_data;

        } catch (\Throwable $th) {
            DB::rollback();
            return [];
        }

    }

    private function update_guests($table, $update_data)
    {
        try {
            DB::beginTransaction();

            $updated_data = collect($update_data)->map(function ($item) use ($table) {
                $item_object_to_array_collection_to_update = collect($item)
                    ->except(['id'])
                    ->map(function ($item, $key) {
                        if ($key === 'created_at') return Carbon::parse($item);
                        else if ($key === 'updated_at') return Carbon::now();
                        return $item;
                    })
                    ->toArray();
                
                $guest = DB::connection('camaya_booking_db')
                    ->table($table)
                    ->find($item->id);
                
                // update only when current status is arriving
                if ($guest->status === 'arriving') {
                    DB::connection('camaya_booking_db')
                        ->table($table)
                        ->where('id', $item->id)
                        ->update($item_object_to_array_collection_to_update);
                }

                return [
                    'local_id' => $item->id,
                    'cloud_id' => $item->id
                ];
            });

            DB::commit();
            return $updated_data;

        } catch (\Throwable $th) {
            DB::rollback();
            return [];
        }

    }
}
