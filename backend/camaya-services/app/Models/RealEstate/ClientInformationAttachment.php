<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class ClientInformationAttachment extends Model
{
    //

    protected $fillable = [
        'client_information_id',
        // 'related_id'
        'type',
        'file_name',
        'content_type',
        'file_size',
        'file_path',
        'description',
        'created_by',
        'deleted_by',
    ];

}
