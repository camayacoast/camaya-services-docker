<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    //

    use SoftDeletes;

    protected $connection = 'camaya_booking_db';
    protected $table = 'invoices';

    protected $fillable = [
        'booking_reference_number',
        'reference_number',
        'batch_number',
        'status',
        'due_datetime',
        'paid_at',
        'total_cost',
        'discount',
        'sales_tax',
        'grand_total',
        'total_payment',
        'balance',
        'change',
        'remarks',
        'created_by',
        'deleted_by',
    ];


    public function inclusions()
    {
        return $this->hasMany('App\Models\Booking\Inclusion', 'invoice_id', 'id')->whereNull('parent_id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\Booking\Payment', 'invoice_id', 'id');
    }
}
