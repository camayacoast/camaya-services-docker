<?php

namespace App\Models\AutoGate;

use Illuminate\Database\Eloquent\Model;

use App\Models\Booking\Stub;
use Carbon\Carbon;

class Pass extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'passes';

    protected $fillable = [
        'booking_reference_number',
        'guest_reference_number',
        'trip_id',
        'card_number',
        'inclusion_id',
        'pass_code',
        'description',
        'category', // (consumable, reusable)
        'count',
        'max',
        'interfaces',
        'mode', // entry, exit, redeem
        'type', // (guest_pass, parking_gate_pass)
        'status', // (consumed, voided)
        'usable_at',
        'expires_at',
        'created_by',
        'deleted_by',
        'deleted_at',
    ];

    protected $casts = [
        'interfaces' => 'array'
    ];

    public static function generate()
    {
        $pass_code = "CMY-".\Str::upper(\Str::random(6));

        // Creates a new reference number if it encounters duplicate
        while (Pass::where('pass_code', $pass_code)->exists()) {
            $pass_code = "CMY-".\Str::upper(\Str::random(6));
        }

        return $pass_code;
    }

    public function tripBooking()
    {
        return $this->hasMany('App\Models\Transportation\Trip', 'guest_reference_number', 'guest_reference_number');
    }

    public function trip()
    {
        return $this->hasOne('App\Models\Transportation\Trip', 'id', 'trip_id');
    }

    public static function createProductPasses($stub_id, $booking_reference_number, $guest_reference_number, $arrival_date, $departure_date, $inclusion_id = null) {
            $stub_details = Stub::where('id', $stub_id)->first();

            $usable_format = $stub_details['starttime'] ? "YYYY-MM-DD ".$stub_details['starttime'] : "YYYY-MM-DD 00:00:00";
            $expires_format = $stub_details['endtime'] ? "YYYY-MM-DD ".$stub_details['endtime'] : "YYYY-MM-DD 23:59:00";

            $passExists = self::where('booking_reference_number', $booking_reference_number)
                                ->where('guest_reference_number', $guest_reference_number)
                                ->where('type', $stub_details['type'])
                                ->first();

            if ($passExists) {
                if ($passExists->max == NULL || ($passExists->count + $stub_details['count']) <= $passExists->max) {
                    if ($stub_details->category != 'reusable') {
                        self::where('id', $passExists->id)
                            ->increment('count', $stub_details['count']);
                    }
                }
            } else {

                $newPass = self::create([
                        'booking_reference_number' => $booking_reference_number,
                        'guest_reference_number' => $guest_reference_number,
                        'type' => $stub_details['type'],
                        // 'card_number' => $card_number,
                        'inclusion_id' => $inclusion_id, 
                        'pass_code' => self::generate(),
                        'category' => $stub_details['category'],
                        'count' => $stub_details['count'],
                        'max' => $stub_details['max'],
                        'interfaces' => $stub_details['interfaces'],
                        'mode' => $stub_details['mode'],
                        'status' => 'created',
                        'usable_at' => $arrival_date->isoFormat($usable_format),
                        'expires_at' => $departure_date->isoFormat($expires_format),
                    ]
                );

                return $newPass;
                
            }
    }

    public static function createPassesByType($stubs, $booking_reference_number, $guest_reference_number, $arrival_date, $departure_date) {
        $stubs_list = Stub::whereIn('type', $stubs)->get();

        $passes_created = [];

        foreach ($stubs_list as $stub_details) {

            $usable_format = $stub_details['starttime'] ? "YYYY-MM-DD ".$stub_details['starttime'] : "YYYY-MM-DD 00:00:00";
            $expires_format = $stub_details['endtime'] ? "YYYY-MM-DD ".$stub_details['endtime'] : "YYYY-MM-DD 23:59:00";

            $passExists = self::where('booking_reference_number', $booking_reference_number)
                                ->where('guest_reference_number', $guest_reference_number)
                                ->where('type', $stub_details['type'])
                                ->first();

            if ($passExists) {
                if ($passExists->max == NULL || ($passExists->count + $stub_details['count']) <= $passExists->max) {
                    self::where('id', $passExists->id)
                        ->increment('count', $stub_details['count']);
                }
            } else {

                $newPass = self::create([
                        'booking_reference_number' => $booking_reference_number,
                        'guest_reference_number' => $guest_reference_number,
                        'type' => $stub_details['type'],
                        // 'card_number' => $card_number,
                        // 'inclusion_id' => null, 
                        'pass_code' => self::generate(),
                        'category' => $stub_details['category'],
                        'count' => $stub_details['count'],
                        'max' => $stub_details['max'],
                        'interfaces' => $stub_details['interfaces'],
                        'mode' => $stub_details['mode'],
                        'status' => 'created',
                        'usable_at' => $arrival_date->isoFormat($usable_format),
                        'expires_at' => $departure_date->isoFormat($expires_format),
                    ]
                );

                $passes_created[] = $newPass;
                
            }
        }

        return $passes_created;
    }

    public static function createBoardingPass($booking_reference_number, $guest_reference_number, $trip_id, $trip_number, $seat_number, $date, $boarding_time, $boarding_time_expires, $inclusion_id = null) {
        
        $usable_format = "YYYY-MM-DD ".$boarding_time;
        $expires_format = "YYYY-MM-DD ".$boarding_time_expires;

        $type = "Boarding Pass: ".$trip_number;

        $passExists = self::where('booking_reference_number', $booking_reference_number)
                            ->where('guest_reference_number', $guest_reference_number)
                            ->where('type', $type)
                            ->whereNotIn('status', ['voided'])
                            ->first();

        if (!$passExists) {

            $newPass = self::create([
                    'booking_reference_number' => $booking_reference_number,
                    'guest_reference_number' => $guest_reference_number,
                    'type' => $type,
                    // 'card_number' => $card_number,
                    'inclusion_id' => $inclusion_id, 
                    'trip_id' => $trip_id,
                    'pass_code' => self::generate(),
                    'category' => 'consumable',
                    'count' => 1,
                    'max' => 1,
                    'interfaces' => ["boarding_gate"],
                    'mode' => 'boarding',
                    'status' => 'created',
                    'usable_at' => $date." 00:00:00",
                    // 'usable_at' => $date." ".$boarding_time,
                    // 'expires_at' => $date." ".$boarding_time_expires,
                    'expires_at' => $date." 23:59:59",
                ]
            );

            return $newPass;
        }
    }


    // Update boarding pass when changed seat number
    public static function updateBoardingPass($pass_id) {
        // NOT YET IMPLEMENTED
    }

    public function guest() {
        return $this->belongsTo('App\Models\Booking\Guest', 'guest_reference_number', 'reference_number');
    }

    public function booking() {
        return $this->hasOne('App\Models\Booking\Booking', 'reference_number', 'booking_reference_number');
    }

    public function customer()
    {
        return $this->hasOne('App\Models\Booking\Customer', 'id', 'customer_id');
    }

    public function valid_taps() {
        return $this->hasMany('App\Models\AutoGate\Tap', 'pass_code')->where('taps.status', 'valid');
    }
}
