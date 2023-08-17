<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Voucher;
use App\Models\Booking\GeneratedVoucher;
use App\Models\Booking\Customer;
use Carbon\Carbon;

use App\Mail\Booking\VoucherPending;
use Illuminate\Support\Facades\Mail;

class BuyVoucher extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
        // return $request->all();

        $vouchers_to_purchase = [];

        $vouchers = Voucher::whereIn('id', collect($request->selected_vouchers)->pluck('id')->all())->get();

        foreach ($request->selected_vouchers as $selected_voucher) {

            $voucher = collect($vouchers)->first(function ($value, $key) use ($selected_voucher) {
                return $value['id'] == $selected_voucher['id'];
            });

            $vouchers_to_purchase[] = [
                'voucher' => $voucher,
                'quantity' => $selected_voucher['quantity'],
            ];

        }

        /**
         * Create customer record
         */

        $newCustomer = Customer::firstOrCreate(
            ['email' => $request->email],
            [
            'first_name' => $request->first_name,
            // 'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'nationality' => $request->nationality ?? 'none',
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'email' => $request->email,
            'created_by' => null,
        ]);

        
        $newVouchers = [];
        // return $vouchers_to_purchase;
        $transaction_reference_number = "VT-".\Str::upper(\Str::random(6));
            
        // Creates a new reference number if it encounters duplicate
        while (GeneratedVoucher::where('transaction_reference_number', $transaction_reference_number)->exists()) {
            $transaction_reference_number = "VT-".\Str::upper(\Str::random(6));
        }

        foreach ($vouchers_to_purchase as $voucher) {
            /**
             * Generate New Unique Voucher Code
             */ 
            if ($voucher['quantity'] > 0) {
                for ($i = 1; $i <= $voucher['quantity']; $i++) {
                    $prefix = "SVD";

                    if ($voucher['voucher']['availability'] == 'for_overnight') {
                        $prefix = "SVO";
                    }
            
                    $voucher_code = $prefix."-".\Str::upper(\Str::random(6));
            
                    // Creates a new reference number if it encounters duplicate
                    while (GeneratedVoucher::where('voucher_code', $voucher_code)->exists()) {
                        $voucher_code = $prefix."-".\Str::upper(\Str::random(6));
                    }
            
                    // return $voucher_code;
        
        
                    $newVouchers[] = GeneratedVoucher::create([
                        'transaction_reference_number' => $transaction_reference_number,
                        'customer_id' => $newCustomer->id,
                        'voucher_id' => $voucher['voucher']['id'],
                        'voucher_code' => $voucher_code,
                        'type' => $voucher['voucher']['type'],
                        'description' => $voucher['voucher']['description'],
                        'availability' => $voucher['voucher']['availability'],
                        'category' => $voucher['voucher']['category'],
                        'mode_of_transportation' => $voucher['voucher']['mode_of_transportation'],
                        'allowed_days' => $voucher['voucher']['allowed_days'],
                        'exclude_days' => $voucher['voucher']['exclude_days'],
                        'validity_start_date' => Carbon::parse(strtotime($voucher['voucher']['booking_start_date']))->setTimezone('Asia/Manila'),
                        'validity_end_date' => Carbon::parse(strtotime($voucher['voucher']['booking_end_date']))->setTimezone('Asia/Manila'),
            
                        'price' => $voucher['voucher']['price'],
            
                        'voucher_status' => 'new',
                        'used_at' => null,
            
                        'payment_status' => 'unpaid',
                        'paid_at' => null,
            
                        'created_by' => null
                    ]);
                }
            }
 
        }

        /**
         * Send email notification
         */

        Mail::to($newCustomer->email)
                ->send(new VoucherPending($transaction_reference_number));

        return [
            'transaction_reference_number' => $transaction_reference_number,
            'customer' => $newCustomer
        ];
    }
}
