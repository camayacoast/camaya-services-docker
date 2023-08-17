<?php

namespace App\Mail\OneBITS;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FailedBooking extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
                ->subject('Booking has failed')
                ->from('booking@1bataanits.ph', '1Bataan Integrated Transport System')
                ->cc(env('APP_ENV') == 'production' ? 'booking@1bataanits.ph' : 'bellenymeria@gmail.com')
                // ->with([
                //         'booking' => $this->booking
                // ])
                // ->attachData($booking_confirmation_pdf->output(), ' BOOKING '."-".$this->reference_number.'.pdf', [
                //         'mime' => 'application/pdf',
                // ])
                ->markdown('emails.onebits.failed_booking');
    }
}
