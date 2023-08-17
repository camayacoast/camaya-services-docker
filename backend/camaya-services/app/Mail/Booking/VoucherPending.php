<?php

namespace App\Mail\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\Booking\GeneratedVoucher;
// use PDF;
use Barryvdh\DomPDF\Facade as PDF;

class VoucherPending extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $generated_vouchers;
    protected $transaction_reference_number;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($transaction_reference_number)
    {
        //
        $this->transaction_reference_number = $transaction_reference_number;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        // $generated_vouchers = GeneratedVoucher::with('voucher.images')->where('transaction_reference_number', $this->transaction_reference_number)->get();
        $generated_vouchers = GeneratedVoucher::with('voucher')->where('transaction_reference_number', $this->transaction_reference_number)->whereNull('paid_at')->get();

        $pdf = PDF::loadView('pdf.booking.voucher_pending', ['transaction_reference_number' => $this->transaction_reference_number, 'generated_vouchers' => $generated_vouchers]);

        $this->subject('Camaya Voucher Pending Payment | Transaction ref #:'. $this->transaction_reference_number.'')
                ->from('reservations@camayacoast.com', 'Camaya Reservations')
                ->cc(env('APP_ENV') == 'production' ? 'reservations@camayacoast.com' : 'kit.seno@camayacoast.com')
                ->with([
                        'customer' => $generated_vouchers[0]['customer'],
                        'transaction_reference_number' => $this->transaction_reference_number,
                ])
                ->attachData($pdf->output(), ' VOUCHER PENDING PAYMENT '.$this->transaction_reference_number.'.pdf', [
                        'mime' => 'application/pdf',
                ])->markdown('emails.booking.voucher_pending');
        
        return $this;
                        
    }
}
