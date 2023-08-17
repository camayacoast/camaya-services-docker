<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class PaymentDetailAttachment extends Model
{
    protected $fillable = [
        'transaction_id',
        'type',
        'file_name',
        'content_type',
        'file_size',
        'file_path',
        'uploaded_by',
        'created_by',
        'deleted_by',
    ];
}