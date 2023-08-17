<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class ClientSpouse extends Model
{
    //
    protected $fillable = [
        'client_id',
        'spouse_first_name',
        'spouse_middle_name',
        'spouse_last_name',
        'spouse_extension',
        'spouse_gender',
        'spouse_birth_date',
        'spouse_birth_place',
        'spouse_citizenship',
        'spouse_government_issued_identification',
        'spouse_id_issuance_place',
        'spouse_id_issuance_date',
        'spouse_tax_identification_number',
        'spouse_occupation',
        'spouse_company_name',
        'spouse_company_address',
    ];
}
