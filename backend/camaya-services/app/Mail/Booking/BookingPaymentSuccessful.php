<?php

namespace App\Mail\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\Booking\Booking;
// use PDF;
use Barryvdh\DomPDF\Facade as PDF;

class BookingPaymentSuccessful extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $booking;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Booking $booking)
    {
        //
        $this->booking = $booking;
        // $this->camaya_transportations = $camaya_transportations;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        // $booking_confirmation_pdf = PDF::loadView('pdf.booking.booking_confirmation', ['booking' => $this->booking, 'camaya_transportations' => $this->camaya_transportations]);

        $this->subject('Booking Payment Successful | Booking ref #:'. $this->booking['reference_number'])
                ->from('reservations@camayacoast.com', 'Camaya Reservations')
                ->cc(env('APP_ENV') == 'production' ? 'reservations@camayacoast.com' : 'kit.seno@camayacoast.com')
                ->with([
                        'booking' => $this->booking
                ])
                // ->attachData($booking_confirmation_pdf->output(), 'BOOKING CONFIRMATION '.$this->booking['reference_number'].'.pdf', [
                //         'mime' => 'application/pdf',
                // ])
                // ->attach(public_path() . '/attachments/house_rules_and_hotel_resort_guidelines_and_policies_sept7_compressed.pdf', [
                //     'as' => 'RESORT_GUIDELINES.pdf',
                //     'mime' => 'application/pdf',
                // ])
                ->attach(public_path() . '/attachments/camaya_vaccination_update_0906_compressed.pdf', [
                    'as' => 'CAMAYA_VACCINATION_UPDATE_0906.pdf',
                    'mime' => 'application/pdf',
                ])
                ->markdown('emails.booking.booking_payment_successful');

        return $this;
    }
}
