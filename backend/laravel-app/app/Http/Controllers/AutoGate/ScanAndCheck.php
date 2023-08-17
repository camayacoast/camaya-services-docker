<?php

namespace App\Http\Controllers\AutoGate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\Guest;

use App\Models\AutoGate\Pass;
use App\Models\AutoGate\Tap;

use App\Models\Transportation\Trip;

use Carbon\Carbon;

class ScanAndCheck extends Controller
{

    /**
     * Accepted parameters
     *      (String) code: GUEST REFERENCE NUMBER, BOOKING REFERENCE NUMBER
     *      (String) interface: 'commercial_gate', 'main_gate', 'parking_gate'
     *      (String) mode: 'entry', 'exit', 'access', 'consume'
     */

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

        // Passes
        // Taps

        $code_type = null;
        $details = [];

        $explodedCode = explode('-', $request->code);

        $prefix = $explodedCode[0] ?? null;

        if (in_array($prefix, ['DT', 'ON'])) {
            // Booking code scanned
            $code_type = 'booking';

            // $details = Booking::where('reference_number', $request->code)->first();

            // Check in here
            
        } else if ($prefix == 'G') {
            // Guest code scanned
            $code_type = 'guest';

        }

        // Get pass based on interface used
        $pass = Pass::where('guest_reference_number', $request->code)
                            ->where('mode', $request->mode)
                            // ->orWhere('card_number', $request->code) // Implement for RFID
                            ->whereRaw('json_contains(interfaces, \'["'. $request->interface .'"]\')')
                            // ->orWhere('type', $request->pass_type)
                            ->where( function ($q) use ($request) {

                                if ($request->type) {
                                    $q->where('type', $request->type);
                                }

                                if ($request->interface == 'boarding_gate' && $request->mode == 'boarding') {
                                    $q->whereHas('trip', function ($q) use ($request) {
                                        $q->where('trip_number', $request->trip_number);
                                    });
                                }
                            })
                            ->latest('created_at')
                            ->first();

        if ($pass->status == 'voided') {
            // Log the tap
            Tap::create([
                'code' => $request->code,
                'tap_datetime' => Carbon::now(),
                'status' => 'pass_voided',
                'message' => '',
                'location' => $request->interface,
                'kiosk_id' => $request->user()->id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'error' => 'PASSES_VOIDED',
                'message' => 'Code not accepted. Passes ['.$request->code.'] is Voided.',
            ],400);
        }

        $booking = Booking::where('reference_number', $pass['booking_reference_number'])
                            ->first();

        // Check if guests exists
        $details = Guest::where('reference_number', $request->code)->first();

        // Get trip status
        if (isset($pass->trip_id)) {
            $trip = Trip::where('id', $pass->trip_id)->first();
    
            if (isset($trip) && isset($trip->status)) {

                if ($trip->status == 'cancelled' || $trip->status == 'no_show') {
                    // Log the tap
                    Tap::create([
                        'code' => $request->code,
                        'tap_datetime' => Carbon::now(),
                        'status' => 'passenger_status_'.$trip->status,
                        'message' => '',
                        'location' => $request->interface,
                        'kiosk_id' => $request->user()->id,
                        'type' => $request->mode, // entry, exit, consume
                    ]);



                    return response()->json([
                        'error' => 'PASSENGER_STATUS_'.strtoupper($trip->status),
                        'message' => 'Code accepted. Passenger status is '.$trip->status.'.',
                    ],400);
                }

            }
        }

        if (!$details) {
            // Log the tap
            Tap::create([
                'code' => $request->code,
                'tap_datetime' => Carbon::now(),
                'status' => 'guest_not_found',
                'message' => '',
                'location' => $request->interface,
                'kiosk_id' => $request->user()->id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'error' => 'GUEST_NOT_FOUND',
                'message' => 'Code not accepted. No guest with code ['.$request->code.'].',
            ],400);
        }

        if ($booking && !in_array($booking['status'], ['confirmed'])) {
            // Log the tap
            Tap::create([
                'code' => $request->code,
                'tap_datetime' => Carbon::now(),
                'status' => 'valid_booking_not_confirmed',
                'message' => '',
                'location' => $request->interface,
                'kiosk_id' => $request->user()->id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'error' => 'PASS_CODE_VALID_WITH_ERROR',
                'message' => 'Code not accepted. Booking is not CONFIRMED.',
            ],400);
        }

        if (!$pass) {
            // Log the tap
            Tap::create([
                'code' => $request->code,
                'tap_datetime' => Carbon::now(),
                'status' => 'invalid',
                'message' => '',
                'location' => $request->interface,
                'kiosk_id' => $request->user()->id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'error' => 'PASS_CODE_INVALID',
                'message' => 'Code not accepted. The code is not allowed to be used here or does not exist in our records.',
            ],400);
        }

        /**
         * Check if $pass has already been consumed
         */
        if (
            $pass->category == 'consumable' &&
            $pass->status == 'consumed'
        ) {
            
            Tap::create([
                'code' => $request->code,
                'tap_datetime' => Carbon::now(),
                'status' => 'valid_not_allowed',
                'message' => '',
                'location' => $request->interface,
                'kiosk_id' => $request->user()->id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'error' => 'PASS_CODE_CONSUMED',
                'message' => 'This code is already been used or consumed completely.',
            ],400);
        }

        /**
         * Check if $pass can already be used
         */
        if (Carbon::now() <= $pass->usable_at) {

            Tap::create([
                'code' => $request->code,
                'tap_datetime' => Carbon::now(),
                'status' => 'valid_not_yet_started',
                'message' => '',
                'location' => $request->interface,
                'kiosk_id' => $request->user()->id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'error' => 'VALID_NOT_YET_ALLOWED',
                'message' => 'Your code is valid but not yet allowed to be used.',
            ],400);
        }

        /**
         * Check if $pass is expired
         */
        if (Carbon::now() > $pass->expires_at) {

            Tap::create([
                'code' => $request->code,
                'tap_datetime' => Carbon::now(),
                'status' => 'valid_expired',
                'message' => '',
                'location' => $request->interface,
                'kiosk_id' => $request->user()->id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'error' => 'VALID_NOT_EXPIRED',
                'message' => 'Your code is valid but it is expired already.',
            ],400);
        }

        
        // Switch intefaces for further checking and details setting
        switch ($request->interface) {
            case 'boarding_gate':
                if ($pass->trip_id) {
                    Trip::where('id', $pass->trip_id)
                        ->update([
                            'status' => 'boarded',
                            'boarded_at' => Carbon::now(),
                        ]);
                    
                    // 71. Automatic tag of Checked-in thru Manual Boarding on Ferry
                    // $details = Guest::where('reference_number', $request->code)->first();
                    
                    if ($details) {
                        Guest::where('id', $details->id)
                            ->update([
                                'status' => 'checked_in',
                                'updated_at' => Carbon::now(),
                            ]);
                    }
                    // 71. end
                }

            case 'commercial_gate':
            case 'main_gate':

                $exists = Booking::where('reference_number', $request->code)->exists();

                if ($code_type == 'booking' && $exists) {

                    Tap::create([
                        'code' => $request->code,
                        'tap_datetime' => Carbon::now(),
                        'status' => 'valid_not_allowed',
                        'message' => '',
                        'location' => $request->interface,
                        'kiosk_id' => $request->user()->id,
                        'type' => $request->mode, // entry, exit, consume
                    ]);
        
                    return response()->json([
                        'error' => 'PASS_CODE_NOT_ALLOWED',
                        'message' => 'Please use your Guest Reference # for Commercial Gate',
                    ],400);
                    
                }

                /**
                 * Update guest status
                 */
                if ($details) {
                    Guest::where('id', $details->id)
                        ->update([
                            'status' => 'checked_in',
                            'updated_at' => Carbon::now(),
                        ]);
                }

            break;
            case 'snack_pack_redemption':
                // $details = Guest::where('reference_number', $request->code)->first();
                break;
        }


        /**
         * Check if record exists in database
         */
        if (!$details) {

            Tap::create([
                'code' => $request->code,
                'tap_datetime' => Carbon::now(),
                'status' => 'invalid',
                'message' => '',
                'location' => $request->interface,
                'kiosk_id' => $request->user()->id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'error' => 'PASS_CODE_INVALID',
                'message' => 'There\'s no record corresponding to Code.',
            ],400);

        }


        /**
         * Create a log of tap
         */
        Tap::create([
            'code' => $request->code,
            'tap_datetime' => Carbon::now(),
            'status' => 'valid',
            'message' => '',
            'location' => $request->interface,
            'kiosk_id' => $request->user()->id,
            'type' => $request->mode, // entry, exit, consume
            'pass_code' => $pass['pass_code'], // entry, exit, consume
        ]);

        /**
         * Update the $pass if necessary
         */

        if ($pass->category == 'consumable') {
            Pass::where('id', $pass->id)
                ->update([
                    'count' => $pass->count - 1, // Update count
                    'status' => (($pass->count - 1) <= 0) ? 'consumed' : 'used',
                    'description' => $request->description
                ]);
        } else if ($pass->type == 'reusable') {
            Pass::where('id', $pass->id)
                ->update([
                    'status' => 'used',
                    'updated_at' => Carbon::now(),
                    'description' => $request->description
                ]);
        }

        // Get pass based on interface used
        $pass = Pass::where('guest_reference_number', $request->code)
                    ->where('mode', $request->mode)
                    // ->orWhere('card_number', $request->code) // Implement for RFID
                    ->whereRaw('json_contains(interfaces, \'["'. $request->interface .'"]\')')
                    // ->orWhere('type', $request->pass_type)
                    ->first();

        if ($request->interface == 'boarding_gate' && $request->mode == 'boarding') {
            $pass = Pass::where('guest_reference_number', $request->code)
                ->where('mode', $request->mode)
                ->whereRaw('json_contains(interfaces, \'["boarding_gate"]\')')
                ->whereHas('trip', function ($query) use ($request) {
                    $query->where('trip_number', $request->trip_number);
                })
                ->with(['trip' => function ($query) use ($request) {
                    $query->with('schedule.transportation');
                    $query->with('schedule.route.origin');
                    $query->with('schedule.route.destination');
                }])->first();

            // Create tap for commercial entry
            if (isset($pass['trip']) && $pass['trip']['schedule']['route']['destination']['code'] == 'CMY') {

                $commercial_gate_pass = Pass::whereRaw('json_contains(interfaces, \'["commercial_gate"]\')')
                                            ->where('mode', 'entry')
                                            ->where('guest_reference_number', $request->code)
                                            ->first();
                // return [
                //     $commercial_gate_pass,
                //     $pass
                // ];

                if ($commercial_gate_pass) {
                    Pass::where('id', $commercial_gate_pass->id)
                        ->update([
                            'count' => $commercial_gate_pass->count - 1, // Update count
                            'status' => (($commercial_gate_pass->count - 1) <= 0) ? 'consumed' : 'used',
                        ]);
                
                    Tap::create([
                        'code' => $request->code,
                        'tap_datetime' => Carbon::now(),
                        'status' => 'valid',
                        'message' => '',
                        'location' => 'commercial_gate',
                        'kiosk_id' => $request->user()->id,
                        'type' => 'entry', // entry, exit, consume
                        'pass_code' => $commercial_gate_pass->pass_code, // entry, exit, consume
                    ]);
                }
            }
        }

        return response()->json([
            'details' => $details->load('booking'),
            'pass' => $pass,
        ], 200);

        
    }
}
