<?php

namespace App\Mail\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\Booking\GeneratedVoucher;
// use PDF;
use Barryvdh\DomPDF\Facade as PDF;

class VoucherConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $paid_vouchers;
    protected $transaction_reference_number;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($paid_vouchers, $transaction_reference_number)
    {
        //
        $this->transaction_reference_number = $transaction_reference_number;
        $this->paid_vouchers = $paid_vouchers;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $pdf = PDF::loadView('pdf.booking.voucher_confirmation', ['paid_vouchers' => $this->paid_vouchers, 'transaction_reference_number' => $this->transaction_reference_number]);

        $this->subject('Voucher Purchase Confirmation | Transaction ref #:'. $this->transaction_reference_number)
                ->from('reservations@camayacoast.com', 'Camaya Reservations')
                ->cc(env('APP_ENV') == 'production' ? 'reservations@camayacoast.com' : 'kit.seno@camayacoast.com')
                ->with([
                        'customer' => $this->paid_vouchers[0]['customer'],
                        'transaction_reference_number' => $this->transaction_reference_number,
                ])
                ->attachData($pdf->output(), 'VOUCHER CONFIRMATION '.$this->transaction_reference_number.'.pdf', [
                        'mime' => 'application/pdf',
                ])
                ->markdown('emails.booking.voucher_confirmation');

        return $this;
    }
}
