<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

use App\User;
use DB;

class Customer extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'customers';

    protected $fillable = [
        'object_id',
        'first_name',
        'middle_name',
        'last_name',
        'nationality',
        'contact_number',
        'address',
        'email',
        'created_by',
    ];

    protected $appends = ['user_type'];

    public function user()
    {
        return $this->hasOne('App\User', 'object_id', 'object_id');
    }

    public function emailMatch()
    {
        return $this->hasOne('App\User', 'email', 'email');
    }

    public function bookings()
    {
        return $this->hasMany('App\Models\Booking\Booking', 'customer_id', 'id');
    }

    public function getUserTypeAttribute()
    {
        // return User::where('object_id', $this->object_id)->first()->user_type ?? null;
        return DB::connection('mysql')->table('users')->where('object_id', $this->object_id)->first()->user_type ?? null;
    }
}
