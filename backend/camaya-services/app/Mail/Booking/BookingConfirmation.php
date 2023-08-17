<?php

namespace App\Mail\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\Booking\Booking;
// use PDF;
use Barryvdh\DomPDF\Facade as PDF;

class BookingConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $booking;
    protected $camaya_transportations;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Booking $booking, $camaya_transportations)
    {
        //
        $this->booking = $booking;
        $this->camaya_transportations = $camaya_transportations;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $booking_confirmation_pdf = PDF::loadView('pdf.booking.booking_confirmation', ['booking' => $this->booking, 'camaya_transportations' => $this->camaya_transportations]);

        $this->subject('Booking Confirmation | Booking ref #:'. $this->booking['reference_number'])
                ->from('reservations@camayacoast.com', 'Camaya Reservations')
                ->cc(env('APP_ENV') == 'production' ? 'reservations@camayacoast.com' : 'kit.seno@camayacoast.com')
                ->with([
                        'booking' => $this->booking
                ])
                ->attachData($booking_confirmation_pdf->output(), 'BOOKING CONFIRMATION '.$this->booking['reference_number'].'.pdf', [
                        'mime' => 'application/pdf',
                ])
                // ->attach(public_path() . '/attachments/house_rules_and_hotel_resort_guidelines_and_policies_sept7_compressed.pdf', [
                //     'as' => 'RESORT_GUIDELINES.pdf',
                //     'mime' => 'application/pdf',
                // ])
                // ->attach(public_path() . '/attachments/camaya_vaccination_update_0906_compressed.pdf', [
                //     'as' => 'CAMAYA_VACCINATION_UPDATE_0906.pdf',
                //     'mime' => 'application/pdf',
                // ])
                ->attach(public_path('images/splashing_getaway.jpg'), [
                    'as' => 'Splashing_Getaway.jpg',
                    'mime' => 'image/jpeg',
                ])
                ->markdown('emails.booking.booking_confirmation');

        foreach ($this->booking['guests'] as $guest) {

            $boarding_pass = PDF::loadView('pdf.booking.boarding_pass', ['guest' => $guest]);

            $pass_type = "GATE PASS";

            if (count($this->camaya_transportations) > 0) {
                $pass_type = "BOARDING PASS";
            }

            $this->attachData($boarding_pass->output(), $guest['first_name'].' '.$guest['last_name'].' '.$guest['reference_number'].' '.$pass_type.'.pdf', [
                'mime' => 'application/pdf',
            ]);
        }

        return $this;
    }
}
