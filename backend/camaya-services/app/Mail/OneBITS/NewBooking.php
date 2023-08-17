<?php

namespace App\Mail\OneBITS;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use DB;
use App\Models\OneBITS\Ticket;
// use PDF;
use Barryvdh\DomPDF\Facade as PDF;

class NewBooking extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $reference_number;
    protected $tickets;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reference_number, $tickets)
    {
        //
        $this->reference_number = $reference_number;
        $this->tickets = $tickets;
    }

    /**
     * Build the message..
     *
     * @return $this
     */
    public function build()
    {
        $booking = $this->getBooking($this->reference_number);
        $booking_confirmation_pdf = PDF::loadView('pdf.onebits.new_booking', ['reference_number' => $this->reference_number, 'tickets' => $this->tickets, 'booking'=>$booking]);

        $this->subject('Confirmed Booking | Booking ref #:'. $this->reference_number)
        ->from('booking@1bataanits.ph', '1Bataan Integrated Transport System')
        ->cc(env('APP_ENV') == 'production' ? 'booking@1bataanits.ph' : 'bellenymeria@gmail.com')
        ->attachData($booking_confirmation_pdf->output(), ' BOOKING '."-".$this->reference_number.'.pdf', [
                'mime' => 'application/pdf',
        ])
        ->markdown('emails.onebits.new_booking');

        // foreach ($this->tickets as $ticket) {

        //     $boarding_pass = PDF::loadView('pdf.onebits.boarding_pass', ['ticket' => $ticket]);

        //     $pass_type = "BOARDING PASS";

        //     $this->attachData($boarding_pass->output(), $this->reference_number.' '.$pass_type.'.pdf', [
        //         'mime' => 'application/pdf',
        //     ]);
        // }

        $boarding_pass = PDF::loadView('pdf.onebits.boarding_pass', ['tickets' => $this->tickets]);

        $pass_type = "BOARDING PASS";

        $this->attachData($boarding_pass->output(), $this->reference_number.' '.$pass_type.'.pdf', [
                'mime' => 'application/pdf',
        ]);


        return $this;
    }

    public function getBooking($ref){
        $bookings = Ticket::join('schedules', 'schedules.trip_number', '=', 'tickets.trip_number')
                        ->leftJoin('routes', 'schedules.route_id', '=', 'routes.id')
                        ->leftJoin('locations as origin', 'routes.origin_id', '=', 'origin.id')
                        ->leftJoin('locations as destination', 'routes.destination_id', '=', 'destination.id')
                        ->where('tickets.group_reference_number', $ref)
                        ->select(DB::raw('count(tickets.reference_number) as ticket_count'),
                            'schedules.trip_date',
                            'group_reference_number',
                            'tickets.trip_number',
                            'tickets.status as ticket_status',
                            'payment_status',
                            DB::raw('sum(tickets.amount) as total_amount'),
                            DB::raw('sum(tickets.discount) as total_discount'),
                            'origin.code as origin_code',
                            'destination.code as destination_code',
                            'tickets.created_at',
                            'tickets.email',
                            'tickets.contact_number',
                            'tickets.payment_provider',
                            'tickets.payment_reference_number',
                            'tickets.payment_provider_reference_number',
                            'tickets.paid_at',
                            'schedules.start_time',
                            'schedules.end_time',

                        )
                        ->groupBy(
                            'tickets.group_reference_number',
                            'tickets.trip_number',
                            'schedules.trip_date',
                            'ticket_status',
                            'payment_status',
                            'origin_code',
                            'destination_code',
                            'tickets.created_at',
                            'tickets.email',
                            'tickets.contact_number',
                            'tickets.payment_provider',
                            'tickets.payment_reference_number',
                            'tickets.payment_provider_reference_number',
                            'tickets.paid_at',
                            'schedules.start_time',
                            'schedules.end_time',
                        )
                        ->get();
                        $array = [];

                        foreach ($bookings->groupBy('group_reference_number')->all() as $key => $item) {
                            $array[] = [
                                'booking_reference_number' => $key,
                                'payment_status' => $item[0]['payment_status'],
                                'ticket_status' => $item[0]['ticket_status'],
                                'trip_date' => $item[0]['trip_date'],
                                'total_booking_amount' => collect($item)->sum('total_amount') - collect($item)->sum('total_discount'),
                                'total_ticket_count' => collect($item)->sum('ticket_count'),
                                'trips' => $item,
                                'booking_date' => $item[0]['created_at'],
                                'email' => $item[0]['email'],
                                'contact_number' => $item[0]['contact_number'],
                                'payment_provider' => $item[0]['payment_provider'],
                                'payment_reference_number' => $item[0]['payment_reference_number'],
                                'payment_provider_reference_number' =>  $item[0]['payment_provider_reference_number'],
                                'paid_at' => $item[0]['paid_at'],
                                'start_time' => $item[0]['start_time'],
                                'end_time' => $item[0]['end_time'],
                            ];
                        }

                        return $array;
    }
}
