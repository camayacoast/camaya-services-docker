<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class ClientProfile extends Model
{
    //
    protected $table = "client_profiles";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'prefix',
        'contact_number',

        'golf_membership',
        'birth_date',
        'birth_place',
        'nationality',
        'residence_address',
        'telephone_number',
        'photo',
        'valid_id',

        'assisted_by',

    ];

    /**
     * Relations
     */

    public function user()
    {
        return $this->belongsTo('App\User', 'id', 'user_id');
    }

    public function checkGolfMemberStatus()
    {
        if (!$this->golf_membership) {
            return 'not a member';
        }

        return Carbon::createFromDate($this->golf_membership)->isPast() ? 'expired' : 'active';
    }
}
