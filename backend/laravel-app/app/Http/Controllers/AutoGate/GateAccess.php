<?php

namespace App\Http\Controllers\AutoGate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\Guest;

use App\Models\AutoGate\Pass;
use App\Models\AutoGate\Tap;

use Carbon\Carbon;

class GateAccess extends Controller
{
    /**
     * Accepted parameters
     *      (String) required code: GUEST REFERENCE NUMBER, BOOKING REFERENCE NUMBER, CARD NUMBER
     *      (String) required interface: 'commercial_gate', 'main_gate', 'parking_gate'
     *      (String) required mode: 'entry', 'exit', 'access', 'consume'
     *      
     *      (String) required secret_token: SECRET_TOKEN
     *      (Integer) required kiosk_id: Kiosk id
     *      (DateTime) required timestamp: DateTime YYYY-MM-DD HH:mm:ss [ISO_FORMAT]
     */

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        $secret_token = 'CAMAYA9999';

        if (($secret_token !== $request->secret_token) || !isset($request->secret_token)) {
            return response()->json([
                'status' => 'INVALID_SECRET_TOKEN',
                'status_message' => "Secret token missing or not recognized.",
            ], 400);
        }

        $code_type = null;
        $details = [];

        $explodedCode = explode('-', $request->code);

        $prefix = $explodedCode[0] ?? null;

        if (in_array($prefix, ['DT', 'ON'])) {
            // Booking code scanned
            $code_type = 'booking';
            
        } else if ($prefix == 'G') {
            // Guest code scanned
            $code_type = 'guest';

        } else {
            $code_type = 'card';
        }


        // Get pass based on interface used
        $pass = Pass::where('guest_reference_number', $request->code)
                        ->where('mode', $request->mode)
                        // ->orWhere('card_number', $request->code) // Implement for RFID
                        ->whereRaw('json_contains(interfaces, \'["'. $request->interface .'"]\')')
                        // ->orWhere('type', $request->pass_type)
                        ->first();

        if (!$pass) {
            // Log the tap
            Tap::create([
                'code' => $request->code,
                'tap_datetime' => Carbon::now(),
                'status' => 'invalid',
                'message' => '',
                'location' => $request->interface,
                'kiosk_id' => $request->kiosk_id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'status' => 'CODE_INVALID',
                'status_message' => 'Code not accepted. The code is not allowed to be used here or does not exist in our records.',
            ],400);
        }

        $booking = Booking::where('reference_number', $pass['booking_reference_number'])
                            ->first();

        if ($booking && !in_array($booking['status'], ['confirmed'])) {
            // Log the tap
            Tap::create([
                'code' => $request->code,
                'tap_datetime' => Carbon::now(),
                'status' => 'valid_booking_not_confirmed',
                'message' => '',
                'location' => $request->interface,
                'kiosk_id' => $request->kiosk_id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'status' => 'CODE_VALID_WITH_ERROR',
                'status_message' => 'Code not accepted. Booking is not CONFIRMED.',
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
                'kiosk_id' => $request->kiosk_id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'status' => 'CODE_CONSUMED',
                'status_message' => 'This code is already been used or consumed completely.',
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
                'kiosk_id' => $request->kiosk_id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'status' => 'VALID_NOT_YET_ALLOWED',
                'status_message' => 'Your code is valid but not yet allowed to be used.',
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
                'kiosk_id' => $request->kiosk_id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'status' => 'VALID_NOT_EXPIRED',
                'status_message' => 'Your code is valid but it is expired already.',
            ],400);
        }

        
        // Switch intefaces for further checking and details setting
        switch ($request->interface) {
            case 'commercial_gate':
            case 'main_gate':
            case 'parking_gate':

                $exists = Booking::where('reference_number', $request->code)->exists();

                if ($code_type == 'booking' && $exists) {

                    Tap::create([
                        'code' => $request->code,
                        'tap_datetime' => Carbon::now(),
                        'status' => 'valid_not_allowed',
                        'message' => '',
                        'location' => $request->interface,
                        'kiosk_id' => $request->kiosk_id,
                        'type' => $request->mode, // entry, exit, consume
                    ]);
        
                    return response()->json([
                        'status' => 'CODE_NOT_ALLOWED',
                        'status_message' => 'Please use your Guest Reference # for Commercial Gate',
                    ],400);
                    
                }
                
                $details = Guest::where('reference_number', $request->code)->with('booking')->first();

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
                'kiosk_id' => $request->kiosk_id,
                'type' => $request->mode, // entry, exit, consume
            ]);

            return response()->json([
                'status' => 'CODE_INVALID',
                'status_message' => 'There\'s no record corresponding to Code.',
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
            'kiosk_id' => $request->kiosk_id,
            'type' => $request->mode, // entry, exit, consume
            'pass_code' => $pass->pass_code, // entry, exit, consume
        ]);

        /**
         * Update the $pass if necessary
         */

        if ($pass->category == 'consumable') {
            Pass::where('id', $pass->id)
                ->update([
                    'count' => $pass->count - 1, // Update count
                    'status' => (($pass->count - 1) <= 0) ? 'consumed' : 'used',
                ]);
        } else if ($pass->type == 'reusable') {
            Pass::where('id', $pass->id)
                ->update([
                    'status' => 'used',
                    'updated_at' => Carbon::now(),
                ]);
        }

        $pass->refresh();

        return response()->json([
            'status' => 'OK',
            'status_message' => "Scan successful!",
            'data' => [ 
                'details' => $details,
                'pass' => $pass
            ]
        ], 200);

        
    }
}
