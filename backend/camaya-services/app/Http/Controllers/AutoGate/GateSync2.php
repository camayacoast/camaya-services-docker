<?php

namespace App\Http\Controllers\AutoGate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\Customer;
use App\Models\Booking\BookingTag;
use App\Models\Booking\Guest;
use App\Models\Booking\Inclusion;
use App\Models\Booking\Invoice;
use App\Models\Booking\GuestVehicle;

use App\Models\AutoGate\Pass;

use Carbon\Carbon;

class GateSync2 extends Controller
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
        
        $data = $request->all();
        
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        
        // return [$year, $month];
        
        // $data =[
        //     'filter_for_inserts_updates' => [
        //         'bookings' => [
        //             'id' => 1,
        //         ],
        //         'booking_tags' => [
        //             'id' => 1,
        //         ],
        //         'customers' => [
        //             'id' => 1,
        //         ],
        //     ]
        // ];
        
        $bookings = $data['filter_for_inserts_updates']['bookings'];
        $customers = $data['filter_for_inserts_updates']['customers'];
        $booking_tags = $data['filter_for_inserts_updates']['booking_tags'];
        

        $booking_ids_from_autogate = collect($bookings)->pluck('id')->all();
        $customer_ids_from_autogate = collect($customers)->pluck('id')->all();
        $booking_tag_ids_from_autogate = collect($booking_tags)->pluck('id')->all();

        /**
         * BOOKINGS
         */

        // Get all bookings that are not available
        $new_bookings_to_sync = Booking::whereNotIn('id', $booking_ids_from_autogate)
                    ->whereYear('start_datetime', '=', $year)
                    ->whereMonth('start_datetime', '=', $month)
                    // ->with('tags')
                    ->with('guests.passes')
                    ->with('guestVehicles')
                    // ->with('inclusions')
                    // ->with('invoices')
                    ->get();

        // Get all bookings that are not synced
        $bookings_to_sync = [];
        $bookings_to_update = Booking::whereIn('id', $booking_ids_from_autogate)
                                    ->whereYear('start_datetime', '=', $year)
                                    ->whereMonth('start_datetime', '=', $month)
                                    ->get();

        // Check if updated_at are not equal
        foreach ($bookings_to_update as $booking_from_live) {
            foreach ($bookings as $booking_from_autogate) {
                // If updated_at are not equal, add booking to array $bookings_to_sync
                if ($booking_from_live['updated_at'] != $booking_from_autogate['updated_at']) {
                    $bookings_to_sync[] = $booking_from_live;
                }
            }
        }

        /**
         * END OF BOOKINGS
         */
         
        /**
         * BOOKING TAGS TO UPDATE
         */
        // Get all bookings that are not synced
        $booking_tags_to_sync = BookingTag::join('bookings', 'bookings.id', '=', 'booking_tags.booking_id')
                                    ->whereIn('bookings.id', $booking_ids_from_autogate)
                                    ->whereYear('bookings.start_datetime', '=', $year)
                                    ->whereMonth('bookings.start_datetime', '=', $month)
                                    ->select('booking_tags.*')
                                    ->get();
        
         /**
         * CUSTOMERS
         */
        $new_customers_to_sync = Customer::whereNotIn('id', $customer_ids_from_autogate)
                    ->get();
                    
        $customers_to_sync = [];
        $customers_to_update = Customer::whereIn('id', $customer_ids_from_autogate)
                    ->get();
                    
        // Check if updated_at are not equal
        foreach ($customers_to_update as $from_live) {
            foreach ($customers as $from_autogate) {
                // If updated_at are not equal, add booking to array $bookings_to_sync
                if ($from_live['updated_at'] != $from_live['updated_at']) {
                    $customers_to_sync[] = $from_live;
                }
            }
        }
        
        /**
         * GUESTS 
         */
        $guests_to_sync = Guest::join('bookings', 'bookings.reference_number', '=', 'guests.booking_reference_number')
                                ->whereIn('bookings.id', $booking_ids_from_autogate)
                                ->whereYear('bookings.start_datetime', '=', $year)
                                ->whereMonth('bookings.start_datetime', '=', $month)
                                ->select('guests.*')
                                ->get();
                                
        /**
         * INCLUSIONS 
         */
        $inclusions_to_sync = Inclusion::join('bookings', 'bookings.reference_number', '=', 'inclusions.booking_reference_number')
                                ->whereIn('bookings.id', $booking_ids_from_autogate)
                                ->whereYear('bookings.start_datetime', '=', $year)
                                ->whereMonth('bookings.start_datetime', '=', $month)
                                ->select('inclusions.*')
                                ->get();
                                
        /**
         * INVOICES 
         */
        $invoices_to_sync = Invoice::join('bookings', 'bookings.reference_number', '=', 'invoices.booking_reference_number')
                                ->whereIn('bookings.id', $booking_ids_from_autogate)
                                ->whereYear('bookings.start_datetime', '=', $year)
                                ->whereMonth('bookings.start_datetime', '=', $month)
                                ->select('invoices.*')
                                ->get();
                                
        /**
         * PASSES 
         */
        $passes_to_sync = Pass::join('bookings', 'bookings.reference_number', '=', 'passes.booking_reference_number')
                                ->whereIn('bookings.id', $booking_ids_from_autogate)
                                ->whereYear('bookings.start_datetime', '=', $year)
                                ->whereMonth('bookings.start_datetime', '=', $month)
                                ->select('passes.*')
                                ->get();
                                
        /**
         * GUEST VEHICLES 
         */
        $guest_vehicles_to_sync = GuestVehicle::join('bookings', 'bookings.reference_number', '=', 'guest_vehicles.booking_reference_number')
                                ->whereIn('bookings.id', $booking_ids_from_autogate)
                                ->whereYear('bookings.start_datetime', '=', $year)
                                ->whereMonth('bookings.start_datetime', '=', $month)
                                ->select('guest_vehicles.*')
                                ->get();

        // Get bookings tags, customers, guests, guest_vehicles, inclusions, invoices, passes
        $new_booking_tags_to_sync = collect($new_bookings_to_sync->load('tags'))->pluck('tags')->all();
        $new_guests_to_sync = collect($new_bookings_to_sync)->pluck('guests')->all();
        $new_inclusions_to_sync = collect($new_bookings_to_sync->load('inclusions'))->pluck('inclusions')->all();
        $new_invoices_to_sync = collect($new_bookings_to_sync->load('invoices'))->pluck('invoices')->all();

        return response()->json([
            'bookings' => [
                'new' => $new_bookings_to_sync,
                'to_update' => $bookings_to_sync,
            ],
            'booking_tags' => [
                'new' => $new_booking_tags_to_sync,
                'to_update' => $booking_tags_to_sync,
            ],
            'customers' => [
                'new' => $new_customers_to_sync,
                'to_update' => $customers_to_sync,
            ],
            'guests' => [
                'new' => $new_guests_to_sync,
                'to_update' => $guests_to_sync,
            ],
            'guest_vehicles' => [
                'to_update' => $guest_vehicles_to_sync,
            ],
            'inclusions' => [
                'new' => $new_inclusions_to_sync,
                'to_update' => $inclusions_to_sync,
            ],
            'invoices' => [
                'new' => $new_invoices_to_sync,
                'to_update' => $invoices_to_sync,
            ],
            'passes' => [
                'to_update' => $passes_to_sync,
            ],
        ], 200);
    }
}
