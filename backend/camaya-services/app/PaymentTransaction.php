<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    //
    protected $table = "payment_transactions";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'transaction_id',
        'item_transaction_id',
        'item',
        'paid_at',
        'refunded_at',
        'payment_type',
        'status',
        'payment_channel',
        'payment_code',
        'remarks',
        'amount',
        'expires_at'
    ];

    public function payer()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function items()
    {
        return $this->hasMany('App\PaymentTransaction', 'transaction_id', 'transaction_id');
    }

    public static function setTransactionStatusToPaid($transaction_id, $code)
    {
        if (!$transaction_id) {
            return 'transaction_id missing';
        }

        $transaction = self::where('transaction_id', $transaction_id);

        $transaction->update([
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s', time()),
            'payment_code' => $code
        ]);

        return $transaction->get();
    }

    public static function setTransactionStatusToCancelled($transaction_id)
    {
        if (!$transaction_id) {
            return 'transaction_id missing';
        }

        $transaction = self::where('transaction_id', $transaction_id)->where('status', 'created');

        if ($transaction) {
            $transaction->update([
                'status' => 'cancelled',
            ]);
        }

        return $transaction->get();
    }
}
