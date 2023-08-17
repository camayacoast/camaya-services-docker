<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class ClientInformation extends Model
{
    //
    //
    protected $table = "client_information";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',

        // CRF
        'extension',
        'contact_number',

        // BIS
        // Other information to be added
        'birth_date',
        'birth_place',
        'citizenship',
        'gender',

        'government_issued_id',
        'government_issued_id_issuance_place',
        'government_issued_id_issuance_date',

        'tax_identification_number',
        'occupation',
        'company_name',
        'company_address',

        'monthly_household_income',

        // Contact information
        'home_phone',
        'business_phone',
        'mobile_number',

        // Address
        'permanent_home_address',
        'current_home_address',
        'office_address',
        'preferred_mailing_address',

        'permanent_home_address_house_number',
        'permanent_home_address_street',
        'permanent_home_address_baranggay',
        'permanent_home_address_city',
        'permanent_home_address_province',
        'permanent_home_address_zip_code',

        'current_home_address_country',
        'current_home_address_international', // International
        'current_home_address_house_number',
        'current_home_address_street',
        'current_home_address_baranggay',
        'current_home_address_city',
        'current_home_address_province',
        'current_home_address_zip_code',

        'status',
        
        'signature',
    ];

    protected $appends = [
        'age',
    ];

    public function getAgeAttribute()
    {
        return Carbon::parse($this->birth_date)->age;
    }

    public function attachments()
    {
        return $this->hasMany('App\Models\RealEstate\ClientInformationAttachment', 'client_information_id', 'id')->orderBy('id', 'desc');
    }
}
