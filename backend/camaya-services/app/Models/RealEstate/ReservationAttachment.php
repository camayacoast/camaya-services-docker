<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class ReservationAttachment extends Model
{
    //

    protected $fillable = [
        'reservation_number',
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
