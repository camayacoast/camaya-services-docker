<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

use App\Mail\Booking\BookingConfirmation;
use Illuminate\Support\Facades\Mail;

class Booking extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'bookings';

    protected $fillable = [
        'user_id',
        'customer_id',

        'agent_id',
        'sales_director_id',

        'reference_number',
        'start_datetime',
        'end_datetime',
        'adult_pax',
        'kid_pax',
        'infant_pax',
        'status',
        'rating',
        'label',
        'remarks',
        'billing_instructions',
        'type',
        'source',
        'portal',
        'mode_of_transportation',
        'eta',
        'mode_of_payment',
        'approved_at',
        'approved_by',
        'auto_cancel_at',
        'cancelled_at',
        'cancelled_by',
        'reason_for_cancellation',
        'created_by',
        'checkout_id'
    ];

    protected $appends = [
        'trip_data',
    ];

    /**
     *  Guests
     */
    public function guests()
    {
        return $this->hasMany('App\Models\Booking\Guest', 'booking_reference_number', 'reference_number')->whereNull('deleted_at');
    }

    public function adultGuests()
    {
        return $this->hasMany('App\Models\Booking\Guest', 'booking_reference_number', 'reference_number')->where('type', 'adult')->whereNull('deleted_at');
    }

    public function kidGuests()
    {
        return $this->hasMany('App\Models\Booking\Guest', 'booking_reference_number', 'reference_number')->where('type', 'kid')->whereNull('deleted_at');
    }

    public function infantGuests()
    {
        return $this->hasMany('App\Models\Booking\Guest', 'booking_reference_number', 'reference_number')->where('type', 'infant')->whereNull('deleted_at');
    }

    /**
     *  Customer Details
     */
    public function customer()
    {
        return $this->belongsTo('App\Models\Booking\Customer', 'customer_id', 'id', 'address');
    }

    /**
     *  User Details
     */
    public function bookingOf()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    /**
     *  User Details
     */
    public function bookedBy()
    {
        return $this->belongsTo('App\User', 'created_by', 'id');
    }

    /**
     *  User Details
     */
    public function cancelled_by()
    {
        return $this->belongsTo('App\User', 'cancelled_by', 'id');
    }

    /**
     *  Activity logs
     */
    public function activity_logs()
    {
        return $this->belongsTo('App\Models\Booking\ActivityLog', 'reference_number', 'booking_reference_number');
    }

    /**
     *  Booking Invoices
     */
    public function invoices()
    {
        return $this->hasMany('App\Models\Booking\Invoice', 'booking_reference_number', 'reference_number');
    }    

    /**
     *  Booking Inclusions
     */
    public function inclusions()
    {
        return $this->hasMany('App\Models\Booking\Inclusion', 'booking_reference_number', 'reference_number');
    }
    public function room_reservations_inclusion()
    {
        return $this->hasMany('App\Models\Booking\Inclusion', 'booking_reference_number', 'reference_number')->where('type', 'room_reservation');
    }

    public function room_reservations()
    {
        return $this->hasMany('App\Models\Hotel\RoomReservation', 'booking_reference_number', 'reference_number')->orderBy('start_datetime', 'asc')->whereIn('status', ['confirmed', 'pending']);
    }

    public function room_reservations_no_filter()
    {
        return $this->hasMany('App\Models\Hotel\RoomReservation', 'booking_reference_number', 'reference_number')->orderBy('start_datetime', 'asc');
    }

    /**
     *  Additional emails
     */
    public function additionalEmails()
    {
        return $this->hasMany('App\Models\Booking\AdditionalEmail', 'booking_id', 'id');
    }

    // public function additional_emails()
    // {
    //     return $this->hasMany('App\Models\Booking\AdditionalEmail', 'booking_id', 'id');
    // }

    /**
     *  Guest vehicles emails
     */
    public function guestVehicles()
    {
        return $this->hasMany('App\Models\Booking\GuestVehicle', 'booking_reference_number', 'reference_number');
    }

    /**
     * Booking notes
     */
    public function notes()
    {
        return $this->hasMany('App\Models\Booking\Note', 'booking_reference_number', 'reference_number')->orderBy('created_at', 'desc');
    }

    /**
     *  Booking tags
     */
    public function tags()
    {
        return $this->hasMany('App\Models\Booking\BookingTag', 'booking_id', 'id');
    }

    /**
     * Total balance of the booking
     */

     public function balance()
     {
         return $this->hasMany('App\Models\Booking\Invoice', 'booking_reference_number', 'reference_number');
     }

     public function attachments()
    {
        return $this->hasMany('App\Models\Booking\Attachment', 'booking_reference_number', 'reference_number')->orderBy('created_at', 'desc');
    }


    /**
     * Passes
     */

    public function passes()
    {
        return $this->hasMany('App\Models\AutoGate\Pass', 'booking_reference_number', 'reference_number');
    }

    public function camaya_transportation()
    {
        return $this->hasMany('App\Models\Transportation\Trip', 'booking_reference_number', 'reference_number');
    }


    /**
     * Confirm booking and send booking confirmation
     */

     public static function confirmAndSendConfirmation()
     {
         
     }

     /**
     *  Payments
     */
    public function booking_payments()
    {
        return $this->hasMany('App\Models\Booking\Payment', 'booking_reference_number', 'reference_number')->where('status', 'confirmed');
    }

    public function pending_payments()
    {
        return $this->hasMany('App\Models\Booking\Payment', 'booking_reference_number', 'reference_number')->where('status', 'pending');
    }


    /**
     *  Booking Golf Carts
     */
    public function golf_cart_inclusions()
    {
        // return $this->hasMany('App\Models\Booking\Inclusion', 'booking_reference_number', 'reference_number')->where('status', 'confirmed');
        return $this->hasMany('App\Models\Booking\Inclusion', 'booking_reference_number', 'reference_number')
                ->where('code', 'like', '%GOLFCART%')
                ->whereIn('type', ['product']);
                // ->orWhere('type', NULL);
    }

    /**
     *  Booking Golf Play
     */
    public function golf_play_inclusions()
    {
        // return $this->hasMany('App\Models\Booking\Inclusion', 'booking_reference_number', 'reference_number')->where('status', 'confirmed');
        return $this->hasMany('App\Models\Booking\Inclusion', 'booking_reference_number', 'reference_number')
                ->whereHas('package', function ($q) {
                    $q->where('category', 'Golf');
                })
                ->whereIn('type', ['package']);
    }

    /* Agent & SD */

    public function agent()
    {
        return $this->hasOne('App\User', 'id', 'agent_id');
    }

    public function sales_director()
    {
        return $this->hasOne('App\User', 'id', 'sales_director_id');
    }

    /**
     * Trips
     */

     public function trips()
     {
         return $this->hasMany('App\Models\Transportation\Trip', 'booking_reference_number', 'reference_number');
     }

    // Check if booking is CMY to MNL only
    public function getTripDataAttribute()
    {
        if ($this->mode_of_transportation == 'camaya_transportation') {
            $trip_numbers = \App\Models\Transportation\Trip::where('booking_reference_number', $this->reference_number)->distinct()->pluck('trip_number');
            $route_ids = \App\Models\Transportation\Schedule::whereIn('trip_number', $trip_numbers)->where('status', 'active')->pluck('route_id');

            $routes = \App\Models\Transportation\Route::whereIn('id', $route_ids)->with('origin')->with('destination')->get();

            $array = [];

            foreach ($routes as $route) {
                $array[] = $route['origin']['code'].'-'.$route['destination']['code'];
            }

            return $array;
        }
    }
}
