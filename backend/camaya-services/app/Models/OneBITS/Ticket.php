<?php

namespace App\Models\OneBITS;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'tickets';

    protected $fillable = [
        'id',
        'reference_number',
        'group_reference_number',
        'trip_number',
        'trip_type',
        'passenger_id',
        'ticket_type',
        'promo_type',

        'amount',
        'discount',
        'discount_id',
        
        'paid_at',
        'payment_reference_number',
        'mode_of_payment', // online, cash
        'payment_status',
        'payment_provider',
        'payment_channel',
        'payment_provider_reference_number',
        
        'voided_by',
        'voided_at',
        'refunded_by',
        'refunded_at',
        
        'remarks',
        'status',

        'contact_number',
        'email',

        'created_at',
        'updated_at'
    ];

    public static function generateReferenceNumber()
    {

        $prefix = "1BITS";

        $reference_number = $prefix."-".\Str::upper(\Str::random(6));

        // Creates a new reference number if it encounters duplicate
        while (Ticket::where('reference_number', $reference_number)->exists()) {
            $reference_number = $prefix."-".\Str::upper(\Str::random(6));
        }

        return $reference_number;

    }

    public static function generateGroupReferenceNumber()
    {

        $prefix = "G-1BITS";

        $reference_number = $prefix."-".\Str::upper(\Str::random(6));

        // Creates a new reference number if it encounters duplicate
        while (Ticket::where('group_reference_number', $reference_number)->exists()) {
            $reference_number = $prefix."-".\Str::upper(\Str::random(6));
        }

        return $reference_number;

    }

    public function passenger()
    {
        return $this->hasOne('App\Models\Transportation\Passenger', 'id', 'passenger_id');
    }

    public function trip()
    {
        return $this->hasOne('App\Models\Transportation\Trip', 'passenger_id', 'passenger_id');
    }

    public function schedule() {
        return $this->hasOne('App\Models\Transportation\Schedule', 'trip_number', 'trip_number');
    }

}
