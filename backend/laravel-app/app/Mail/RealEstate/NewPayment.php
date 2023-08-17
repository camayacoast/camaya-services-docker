<?php

namespace App\Mail\RealEstate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\RealEstate\RealEstatePayment;

class NewPayment extends Mailable
{
    use Queueable, SerializesModels;

    protected $payment;
    protected $mailSubject;
    protected $response;
    protected $payload;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($payment, $mailSubject, $response, $payload)
    {
        //
        $this->payment = $payment;
        $this->mailSubject = $mailSubject;
        $this->response = $response;
        $this->payload = $payload;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->subject($this->mailSubject)
                    ->from('online.payment@camayacoast.com', 'Camaya Real Estate Payment')
                    ->cc(env('APP_ENV') == 'production' ? 'online.payment@camayacoast.com' : 'kit.seno@camayacoast.com')
                    ->with([
                         'payment' => $this->payment,
                         'response' => $this->response,
                         'data' => $this->payload
                    ])
                    ->markdown('emails.realestate.new_payment');
    }

}
