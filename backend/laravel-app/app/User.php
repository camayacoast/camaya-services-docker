<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use App\Models\RealEstate\SalesTeamMember;
use App\Models\Booking\Booking;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $connection = 'mysql';
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'object_id',
        'first_name',
        'middle_name',
        'last_name',
        'user_type',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        // 'parent_team',
        // 'sub_team',
        'other'
    ];

    /**
     * Relations
     */

    public function clientProfile()
    {
        return $this->hasOne('App\ClientProfile', 'user_id', 'id')
                    ->select('prefix', 'contact_number', 'user_id', 'golf_membership');
    }

    public function workInformation()
    {
        return $this->hasOne('App\WorkInformation', 'user_id', 'id')
                    ->select(
                        'prefix',
                        'contact_number',
                        'user_id',
                        'golf_membership',
                        'birth_date',
                        'birth_place',
                        'nationality',
                        'residence_address',
                        'telephone_number',
                        'photo',
                        'valid_id'
                    );
    }

    public function clientProperties()
    {
        return $this->hasMany('App\ClientProperties', 'user_id', 'id');
    }

    public function clientComembers()
    {
        return $this->hasMany('App\ClientComembers', 'user_id', 'id');
    }
    

    public function paymentHistory()
    {
        return $this->hasMany('App\PaymentTransaction', 'user_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'created_by', 'id');
    }

    public function verification()
    {
        return $this->hasOne('App\Verification', 'email', 'email');
    }

    public function reset_password()
    {
        return $this->hasOne('App\ResetPassword', 'email', 'email');
    }

    public function viber_subscription()
    {
        return $this->hasOne('App\ViberSubscriber', 'user_id', 'id');
    }

    public function checkGolfMembership()
    {
        return true;
    }

    public function customerProfile()
    {
        return $this->hasOne('App\Models\Booking\Customer', 'object_id', 'object_id');
    }

    public function team_member_of()
    {
        return $this->belongsTo('App\Models\RealEstate\SalesTeamMember', 'id', 'user_id');
    }

    public function parent_team_details()
    {
        return $this->belongsTo('App\Models\RealEstate\SalesTeamMember', 'id', 'user_id');
    }

    public function owned_team()
    {
        return $this->hasOne('App\Models\RealEstate\SalesTeam', 'owner_id', 'id');
    }

    public function getParentTeamAttribute()
    {
        if ($this->user_type == 'agent') {
            
            $sales_team_member = SalesTeamMember::where('user_id', $this->id)
                                            // ->with('team.teamowner')
                                            ->leftJoin('sales_teams', 'sales_team_members.team_id', '=', 'sales_teams.id')
                                            ->leftJoin('users as owner', 'sales_teams.owner_id', '=', 'owner.id')
                                            ->where('sales_team_members.role','member')
                                            ->select(
                                                'owner.first_name as owner_first_name',
                                                'owner.last_name as owner_last_name',
                                                'sales_teams.name as team_name',
                                                'sales_teams.parent_id as team_parent_id',
                                                'sales_teams.id as team_id',
                                                'sales_teams.owner_id as team_owner_id'
                                            )
                                            ->get()
                                            ->toArray();

            if (!isset($sales_team_member)) return null;
            
            $sales_team = collect($sales_team_member)->first(function ($value, $key) {
                return $value['team_parent_id'] == null;
            });

            // $sales_team_member = [];
            if (!$sales_team) return null;

            $team = [
                'id' => $sales_team['team_id'],
                'name' => $sales_team['team_name'],
                'owner_id' => $sales_team['team_owner_id'],
                'teamowner' => [
                    'first_name' => $sales_team['owner_first_name'],
                    'last_name' => $sales_team['owner_last_name'],
                ],
            ];

            return $team ?? null;
        }

        return null;
    }

    public function getSubTeamAttribute()
    {
        if ($this->user_type == 'agent') {

            $sales_team_member = SalesTeamMember::where('user_id', $this->id)
                                                ->leftJoin('sales_teams', 'sales_team_members.team_id', '=', 'sales_teams.id')
                                                ->leftJoin('users as owner', 'sales_teams.owner_id', '=', 'owner.id')
                                                ->where('sales_team_members.role','member')
                                                ->select(
                                                    'owner.first_name as owner_first_name',
                                                    'owner.last_name as owner_last_name',
                                                    'sales_teams.name as team_name',
                                                    'sales_teams.parent_id as team_parent_id',
                                                    'sales_teams.id as team_id',
                                                    'sales_teams.owner_id as team_owner_id'
                                                )
                                                ->get()
                                                ->toArray();

            
            if (!isset($sales_team_member)) return null;
            
            $sales_team = collect($sales_team_member)->first(function ($value, $key) {
                return $value['team_parent_id'] != null;
            });

            // $sales_team_member = [];
            if (!$sales_team) return null; 

            $team = [
                'id' => $sales_team['team_id'],
                'parent_id' => $sales_team['team_parent_id'],
                'name' => $sales_team['team_name'],
                'owner_id' => $sales_team['team_owner_id'],
                'teamowner' => [
                    'first_name' => $sales_team['owner_first_name'],
                    'last_name' => $sales_team['owner_last_name'],
                ],
            ];

            return $team ?? null;
        }

        return null;
    }

    public function getOtherAttribute()
    {

        if ($this->user_type != 'agent') return [];

        $other = [];

        // $other['allowed_entity'] = $this->parent_team['owner_id'];

        if (isset($this->parent_team) && isset($this->parent_team['owner_id'])) {
            $sales_director = Self::where('id', $this->parent_team['owner_id'])->first();

            if ($sales_director) {
                $entity_match = [
                    'rudolph_25ai@yahoo.com' => 'SD Rudolph Cortez',
                    // 'cressatupaz.organo@gmail.com' => 'SD Rudolph Cortez',
                    'earthfield_marklouie@yahoo.com' => 'SD Louie Paule',
                    'luzsdizon521@yahoo.com' => 'SD Luz Dizon',
                    'johnzuno@gmail.com' => 'SD John Rizaldy Zuno',
                    'brianbeltran@camayacoast.org' => 'SD Brian Beltran',
                    'jadetuazon2005@yahoo.com' => 'SD Jake Tuazon',
                    'joeybayon@gmail.com' => 'SD Joey Bayon',
                    'grace888.camayacoast@yahoo.com' => 'SD Grace Laxa',
                    'stephenbalbin@yahoo.com' => 'SD Stephen Balbin',
                    'maripaulmilanes@yahoo.com.ph' => 'SD Maripaul Milanes',
                    'sdnadcamaya@gmail.com' => 'SD Danny Ngoho',
                    'camayaproperties@gmail.com' => 'SD Harry Colo',
                    'lhotquiambao@hotmail.com' => 'SD Lhot Quiambao',
                ];
    
                $other['allowed_entity'] = isset($entity_match[$sales_director['email']]) ? $entity_match[$sales_director['email']] : null;
            }
        }

        return $other;
    }

    public static function agentObjectIds()
    {
        return self::where('user_type', 'agent')->pluck('object_id');
    }

    /**
     * Parent team
     */

    public function parent_team()
    {
        return $this->belongsTo('App\Models\RealEstate\SalesTeamMember', 'id', 'user_id');
    }

    public static function scopeParentTeam($query)
    {

        return $query->with(['parent_team' => function ($q) {
            $q->select('id','team_id','user_id','role');
            $q->where('role', 'member');
            $q->whereNull('deleted_at');
            $q->whereHas('team', function ($q) {
                $q->whereNull('parent_id');
            });
            $q->with(['team' => function ($q) {
                $q->select('id', 'owner_id', 'name');
                $q->with('teamowner:id,first_name,last_name');
            }]);
        }]);
    }

    /**
     * Sub team
     */

    public function sub_team()
    {
        return $this->belongsTo('App\Models\RealEstate\SalesTeamMember', 'id', 'user_id');
    }

    public static function scopeSubTeam($query)
    {

        return $query->with(['sub_team' => function ($q) {
            $q->select('id','team_id','user_id','role');
            $q->where('role', 'member');
            $q->whereNull('deleted_at');
            $q->whereHas('team', function ($q) {
                $q->whereNotNull('parent_id');
            });
            $q->with(['team' => function ($q) {
                $q->select('id', 'owner_id', 'name');
                $q->with('teamowner:id,first_name,last_name');
            }]);
        }]);
    }

}
