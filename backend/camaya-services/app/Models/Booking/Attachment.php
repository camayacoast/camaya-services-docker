<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'attachments';

    protected $fillable = [
        'booking_reference_number',
        'related_id',
        'type',
        'file_name',
        'content_type',
        'file_size',
        'file_path',
        'description',
        'created_by',
        'deleted_by',
    ];

    public function uploader()
    {
        return $this->hasOne('App\User', 'id', 'created_by');
    }
}
