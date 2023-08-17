<?php

namespace App\Mail\OneBITS;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactUsEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    protected $name;
    protected $email;
    protected $contact;
    protected $message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $email, $contact, $message)
    {
        $this->name = $name;
        $this->email = $email;
        $this->contact = $contact;
        $this->message = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
                ->subject('You have a new inquiry!')
                ->from('booking@1bataanits.ph', '1Bataan Integrated Transport System')
                ->cc($this->email)
                ->with(['name' => $this->name,
                    'email' => $this->email,
                    'contact' => $this->contact,
                    'message' => $this->message])
                // ->attachData($booking_confirmation_pdf->output(), ' BOOKING '."-".$this->reference_number.'.pdf', [
                //         'mime' => 'application/pdf',
                // ])
                ->markdown('emails.onebits.new_inquiry_email');
    }
}
