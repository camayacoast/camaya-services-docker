<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\User;
use Illuminate\Support\Arr;

class GolfPaymentTransaction extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $transactions;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $transactions)
    {
        //
        $this->user = $user;
        $this->transactions = $transactions;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $total_amount = array_sum(Arr::pluck($this->transactions, 'amount'));

        return $this->subject('Golf Payment Confirmation for Transaction: '.$this->transactions[0]->transaction_id)
                    ->from('golfmembership@camayacoast.com', 'Golf Payment')
                    ->cc(env('APP_ENV') == 'production' ? 'golfmembership@camayacoast.com' : 'kit.seno@camayacoast.com')
                    ->with([
                        'transactions' => $this->transactions,
                        'user' => $this->user,
                        'total_amount' => $total_amount
                    ])
                    ->markdown('emails.payment.golftransaction');
    }
}
