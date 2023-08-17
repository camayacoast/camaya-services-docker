<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SalesAdminPortal\Payment;
use Illuminate\Http\Request;

use Illuminate\Support\Collection;
use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\AmortizationSchedule;
use App\Models\RealEstate\AmortizationPenalty;
use App\Models\RealEstate\RealEstatePayment;
use App\Models\RealEstate\RealEstatePaymentStatus;
use App\Models\RealEstate\CashTermLedger;
use App\Imports\ReservationPaymentImport;
use App\Models\RealEstate\RealestatePaymentActivityLog;
use App\Models\RealEstate\RealestateActivityLog;
use App\Models\RealEstate\CashTermPenalty;
use App\Models\RealEstate\ReservationPromo;

use App\Http\Requests\RealEstate\AddPaymentRequest;

use Carbon\Carbon;

class Payment extends Controller {

    public $waive_penalty = false;

    public function updatePayment(AddPaymentRequest $request)
    {
        // Uncomment after development
        // if (!$request->user()->hasRole(['super-admin'])) {
        //     if ( 
        //         $request->user()->user_type != 'admin' ||
        //         !$request->user()->hasPermissionTo('SalesAdminPortal.UpdatePayment.AmortizationLedger')
        //     ) {
        //         return response()->json(['message' => 'Unauthorized.'], 400);
        //     }
        // }

        $data = [
            'reservation_number' => $request->reservation_number,
            'payment_amount' => $request->payment_amount,
            'discount' => (!is_null($request->discount)) ? $request->discount : 0, 
            'payment_gateway' => $request->payment_gateway,
            'payment_type' => $request->payment_type,
            'payment_gateway_reference_number' => $request->payment_gateway_reference_number,
            'remarks' => $request->remarks,
            'paid_at' => $request->paid_at,
            'bank' => $request->bank,
            'bank_account_number' => $request->bank_account_number,
            'cr_number' => $request->cr_number,
            'or_number' => $request->or_number,
        ];

        if( $request->payment_terms_type === 'cash' ) {
            CashTermLedger::where('transaction_id', $request->transaction_id)->update([
                'date_paid' => $request->paid_at,
                'or_number' => $request->or_number,
                'pr_number' => $request->cr_number,
                'amount_paid' => $request->payment_amount,
                'remarks' => $request->remarks,
                'payment_gateway' => $request->payment_gateway,
                'payment_gateway_reference_number' => $request->payment_gateway_reference_number,
                'payment_type' => $request->payment_type,
                'bank' => $request->bank,
                'bank_account_number' => $request->bank_account_number,
            ]);

            if( $request->payment_type == 'penalty' && isset( $request->cash_term_ledger_id) ) {
                CashTermPenalty::where('id', $request->cash_term_ledger_id)
                    ->where('reservation_number', $request->reservation_number)
                    ->update([
                        'amount_paid' => $request->payment_amount,
                        'remarks' => $request->remarks,
                        'paid_at' => $request->paid_at
                    ]);
            }
        } else {
            if( $request->payment_type == 'reservation_fee_payment' || $request->payment_type == 'downpayment' ){
                $reservation = Reservation::where('reservation_number', $request->reservation_number)->first();
                if( $reservation ) {
                    $this->setReservationToRecalculate([
                        'reservation_number' => $reservation->reservation_number,
                        'payment_terms_type' => $reservation->payment_terms_type
                    ]);
                }
            }
        }

        $updatePayment = RealEstatePayment::where([
            'id' => $request->payment_id
        ])->update($data);

        return $data;
    }

    public function reDashboardUpdatePayment(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('RealEstate.Delete.PaymentDetails') || 
                !$request->user()->hasPermissionTo('SalesAdminPortal.AddPayment.AmortizationLedger')
            ) {
                return response()->json([
                    'message' => 'RealEstate.Delete.PaymentDetails and SalesAdminPortal.AddPayment.AmortizationLedger permission is required.'
                ], 400);
            }
        }

        if( $request->action_type === 'delete' ) {

            $delete = RealEstatePayment::where('id', $request->payment_id)
                ->where('transaction_id', $request->transaction_id)
                ->delete();
            
            if( $request->payment_type == 'monthly_amortization_payment' && !is_null($request->amortization_schedule_id) ) {
                $delete_penalty = RealEstatePayment::where('amortization_schedule_id', $request->amortization_schedule_id)
                    ->where('reservation_number', $request->reservation_number)
                    ->where('client_number', $request->client_number)
                    ->where('payment_type', 'penalty')
                    ->delete();
            }

            $has_client_number = ( is_null($request->client_number) || $request->client_number == '' ) ? false : true;

            if( $request->is_verified == 1 && $has_client_number && ($request->payment_type == 'penalty' || $request->payment_type == 'monthly_amortization_payment') ) {
                $reservation = Reservation::where('client_number', $request->client_number)->with('payment_details')->first();
                
                if( $reservation ) {

                    $payment_details_count = $reservation->payment_details->count();

                    if( $payment_details_count > 0 ) { 

                        Reservation::where('client_number', $request->client_number)->update([
                            'recalculated' => 0
                        ]);

                        $amortization_sched_count = 0;

                        foreach( $reservation->payment_details as $key => $payment ) {
                            if( in_array($payment->payment_type, ['monthly_amortization_payment']) ) {
                                $amortization_sched_count++;
                            }
                        }

                        if( $amortization_sched_count <= 0 ) {
                            $amort_schedule = new AmortizationSchedule;
                            $amort_schedule->reset_amortization_schedule($request->reservation_number);
                        }
                        
                    } else {
                        $amort_schedule = new AmortizationSchedule;
                        $amort_schedule->reset_amortization_schedule($request->reservation_number);
                    }
                }
            } 
            
            if( $request->is_verified == 1 && $has_client_number && ($request->payment_type == 'reservation_fee_payment' || $request->payment_type == 'downpayment') ){
                $reservation = Reservation::where('client_number', $request->client_number)->first();
                if( $reservation ) {
                    $this->setReservationToRecalculate([
                        'reservation_number' => $reservation->reservation_number,
                        'payment_terms_type' => $reservation->payment_terms_type,
                    ]);
                }
            }

            if( $request->is_verified == 1 && 
                $has_client_number && 
                ($request->payment_type == 'full_cash' || $request->payment_type == 'split_cash' || $request->payment_type == 'partial_cash') 
            ){
                
                $reservation = Reservation::where('client_number', $request->client_number)->with('payment_details')->first();

                if( $reservation ) {

                    $payment_details_count = $reservation->payment_details->count();

                    $Payment = new Payment;
                    $request->re_recalculate = true;
                    $Payment->recompute_cash_account($request);
                }

            }

            RealestatePaymentActivityLog::create([
                'action' => 'delete_transaction',
                'description' => 'Delete transaction.',
                'model' => 'App\Models\RealEstate\RealestatePaymentActivityLog',
                'properties' => null,
                'created_by' => $request->user()->id,
            ]);

            return $delete;

        } else {
            $data = [
                'payment_amount' => $request->payment_amount,
                'payment_gateway' => $request->payment_gateway,
                'paid_at' => Carbon::parse($request->paid_at),
            ];
    
            $updatePayment = RealEstatePayment::where([
                'id' => $request->payment_id
            ])->update($data);
    
            $reservation = false;
            $has_client_number = ( is_null($request->client_number) || $request->client_number == '' ) ? false : true;
            $has_reservation_number = (is_null($request->reservation_number) || $request->reservation_number == '') ? false : true;
    
            if( $updatePayment && $request->is_verified == 1 && $has_client_number && ($request->payment_type == 'penalty' || $request->payment_type == 'monthly_amortization_payment') ) {
                $reservation = Reservation::where('client_number', $request->client_number)->first();
                if( $reservation ) {
                    $Payment = new Payment;
                    $request->re_recalculate = true;
                    $Payment->recompute_account($request);
                }
            }

            RealestatePaymentActivityLog::create([
                'action' => 'update_transaction',
                'description' => 'Update transaction.',
                'model' => 'App\Models\RealEstate\RealestatePaymentActivityLog',
                'properties' => null,
                'created_by' => $request->user()->id,
            ]);

            return $updatePayment;
        }
    }

    public function recompute_cash_account(Request $request)
    {
        $reservation_number = $request->reservation_number;
        $re_recalculate = isset($request->re_recalculate) ? $request->re_recalculate : false;
        $collection_recalculate = isset($request->collection_recalculate) ? $request->collection_recalculate : false;

        $reservation = Reservation::where('reservation_number', $reservation_number)->first();

        $payments = Payment::getPaymentDetails($reservation, 'ASC', $this->waive_penalty);
        $reservation['payment_details'] = $payments;

        $has_client_number = ( is_null($reservation->client_number) || $reservation->client_number == '' ) ? false : true;

        if( $has_client_number && $reservation->payment_details->count() > 0 ) {

            $number = 1;
            $cash_term_payment_count = 0;
            $lists_of_payments = [];
            $bulk_payments = [];
            $payment_details = [];
            $bulk_payments[] = new Collection([
                '#', 'DATES', 'AMOUNT', 'CLIENT NUMBER', 'FIRSTNAME', 
                'LASTNAME', 'PAYMENT DESTINATION', 'PAYMENT GATEWAY',
                'BANK', 'CHECK NUMBER', 'BANK ACCOUNT NUMBER', 'OR NUMBER', 'PR NUMBER'
            ]);

            foreach( $reservation->payment_details as $key => $payment ) {
                
                if( in_array($payment->payment_type, ['full_cash', 'split_cash', 'partial_cash']) ) {
                    $cash_term_payment_count++;
                }

                $amount = $payment->payment_amount;
                $paid_at = date('m/d/Y', strtotime($payment->paid_at));
                $client_number = $payment->client_number;
                $first_name = $payment->first_name;
                $last_name = $payment->last_name;
                $payment_type = $payment->payment_type;
                $payment_gateway = $payment->payment_gateway;
                $bank = $payment->bank;
                $check_number = $payment->check_number;
                $bank_account_number = $payment->bank_account_number;
                $or_number = $payment->or_number;
                $pr_number = $payment->cr_number;
                $record_type = $payment->record_type;
                $gateway_ref_number = $payment->payment_gateway_reference_number;
                $remarks = $payment->remarks;
                $payment_encode_type = $payment->payment_encode_type;
                $payment_amortization_id = NULL;

                $penalty_details = [];
                $amortization_details = [];


                $bulk_payments [] = new Collection([
                    (string) $number, $paid_at, $amount, $client_number, $first_name, 
                    $last_name, $payment_type, $payment_gateway,
                    $bank, $check_number, $bank_account_number, $or_number, $pr_number, $record_type, 
                    $payment_amortization_id, $gateway_ref_number, $remarks, $payment_encode_type, $penalty_details, $amortization_details
                ]);

                $lists_of_payments[] = $payment->id;

                $number++;
            }

            if( $cash_term_payment_count <= 0 ) {
                $reset_cash_term = new CashTermLedger;
                $reset_cash_term->reset_cash_term_ledger($request->reservation_number);
            }

            if( count($bulk_payments) > 0 ) {
                Reservation::where('reservation_number', $reservation_number)->update([
                    'recalculated' => 1
                ]);
                RealEstatePayment::whereIn('id', $lists_of_payments)->delete();
                $reset_cash_term = new CashTermLedger;
                $reset_cash_term->reset_cash_term_ledger($request->reservation_number);

                $rows = new Collection($bulk_payments);
                $import = new ReservationPaymentImport;
                $import->recompute = true;
                $import->waive_penalty = $this->waive_penalty;
                $import->user = is_null($request->user()) ? $request->user : $request->user();
                $import->collection($rows);
            }
            

        }

        if( $has_client_number && $reservation->payment_details->count() <= 0 ) {
            $reset_cash_term = new CashTermLedger;
            $reset_cash_term->reset_cash_term_ledger($request->reservation_number);
        }
        
    }

    public function recompute_account(Request $request)
    {
        $reservation_number = $request->reservation_number;
        $request_status = $request->status;
        $request_number = $request->number;
        $request_additional_payment = $request->additional_payment;
        $exclude_penalty = $request->exclude_penalty;
        $re_recalculate = isset($request->re_recalculate) ? $request->re_recalculate : false;
        $collection_recalculate = isset($request->collection_recalculate) ? $request->collection_recalculate : false;

        $reservation = Reservation::where('reservation_number', $reservation_number)->first();

        $payments = Payment::getPaymentDetails($reservation, 'ASC', $this->waive_penalty);
        $reservation['payment_details'] = $payments;

        $has_client_number = ( is_null($reservation->client_number) || $reservation->client_number == '' ) ? false : true;

        $single_payments = [
            'reservation_fee_payment', 'downpayment', 'title_fee', 'retention_fee', 
            'redocs_fee', 'docs_fee', 'split_cash', 'full_cash', 'partial_cash'
        ];
        $multi_payments = ['monthly_amortization_payment', 'penalty'];
        $not_incluede = ['hoa_fees', 'camaya_air_payment', 'others'];

        $lists_of_payments = [];
        $bulk_payments = [];
        $payment_details = [];
        $bulk_payments[] = new Collection([
            '#', 'DATES', 'AMOUNT', 'CLIENT NUMBER', 'FIRSTNAME', 
            'LASTNAME', 'PAYMENT DESTINATION', 'PAYMENT GATEWAY',
            'BANK', 'CHECK NUMBER', 'BANK ACCOUNT NUMBER', 'OR NUMBER', 'PR NUMBER'
        ]);

        if( $has_client_number && $reservation->payment_details->count() > 0 ) {

            $is_waived = null;
            $penalty_number = false;
            if(isset($request->penalty_number)) {

                $is_waived = $request->waive;

                $penalty_number = $request->penalty_number;

                $penalty = AmortizationPenalty::where('reservation_number', $reservation_number)
                    ->where('number', $penalty_number)
                    ->orderBy('number', 'asc')
                    ->with('amortization_schedule:id,due_date,amount,is_collection,number,transaction_id')
                    ->with(['payments' => function($q){
                        $q->orderBy('id', 'ASC');
                    }])
                    ->first();
                
                    
                $penalty_transaction_id = false;
                $penalty_amortization_id = false;
                $penalty_amortization_amount = false;
                $penalty_payment_amount = false;
                $penalty_status = null;
                $penalty_amount = 0;
                $penalty_amount_paid = 0;

                if( $penalty ) {
                    $penalty_amortization_id = $penalty->amortization_schedule->id;
                    $penalty_amortization_amount = $penalty->amortization_schedule->amount;
                    $penalty_amount = $penalty->penalty_amount;
                    $penalty_amount_paid = $penalty->amount_paid;
                    $penalty_status = $penalty->status;
                    $penalty_payment = $penalty->payments->first();

                    if( $penalty_payment ) {
                        $penalty_transaction_id = $penalty_payment->transaction_id;
                        $penalty_payment_amount = $penalty_payment->payment_amount;
                    }
                }

            }

            $number = 1;
            $remaining_balance = 0;
            foreach( $reservation->payment_details as $key => $payment ) {

                if( !in_array($payment->payment_type, $single_payments) && 
                    $payment->is_verified && $payment->payment_type !== null && 
                    $payment->payment_type != '' && 
                    !in_array($payment->payment_type, $not_incluede) ) 
                {

                    $include_payment = true;
                    $payment_amortization_id = $payment->amortization_schedule_id;
                    if( $is_waived !== null ) {
                        if( $is_waived ) {
                            if( $penalty_transaction_id == $payment->transaction_id ) {
                                $include_payment = false;
                                $remaining_balance = $penalty_payment_amount;
                            }
                        } else {
                            if( $penalty_amortization_id == $payment->amortization_schedule_id ) {
                                $remaining_balance = $penalty_amount;
                            } 
                        }
                    }

                    $lists_of_payments[] = $payment->id;

                    if( $include_payment ) {

                        $paid_at = date('m/d/Y', strtotime($payment->paid_at));
                        if ($is_waived !== null) {

                            if( $is_waived ) {

                                $amortization_schedule = AmortizationSchedule::where('reservation_number', $reservation_number)
                                    ->where('id', $payment_amortization_id)->first();
                                
                                if( $amortization_schedule['number'] == $request_number && $exclude_penalty == 1 ) {
                                    $amount = ($payment->payment_amount + $remaining_balance) - $penalty_amount_paid;
                                } else {
                                    $amount = $payment->payment_amount + $remaining_balance;
                                }
                                
                                // $amount = ($penalty_amortization_amount !== false && $amount >= $penalty_amortization_amount ) ? $penalty_amortization_amount : $amount;
                            } else {
                                if( $penalty_status == 'waived' ) {
                                    $amount = $payment->payment_amount;
                                } else {

                                    $amortization_schedule = AmortizationSchedule::where('reservation_number', $reservation_number)
                                        ->where('id', $payment_amortization_id)->first();

                                    if( $amortization_schedule['number'] == $request_number ) {
                                        $amount = (($payment->payment_amount + $remaining_balance) - $penalty_amount ) + $request_additional_payment;
                                    } else {
                                        $amount = $payment->payment_amount + $remaining_balance;
                                    }
                                }

                                // $amount = ($penalty_amortization_amount !== false && $amount >= $penalty_amortization_amount ) ? $penalty_amortization_amount : $amount;
                            }
                            // $amount = ( $is_waived ) ? $payment->payment_amount + $remaining_balance : $payment->payment_amount;
                        } else {
                            $amount = $payment->payment_amount;
                        }
                        
                        $default_field_values = [
                            'first_name' => null,
                            'last_name' => null,
                            'payment_gateway' => null,
                            'bank' => null,
                            'check_number' => null,
                            'bank_account_number' => null,
                            'or_number' => null,
                            'pr_number' => null,
                            'record_type' => null,
                            'payment_gateway_reference_number' => null,
                            'remarks' => null,
                            'payment_encode_type' => 'admin',
                            'paid_at' => null,
                        ];
                        $penalty_details = $default_field_values;
                        $amortization_details = $default_field_values;

                        $client_number = $payment->client_number;
                        $first_name = $payment->first_name;
                        $last_name = $payment->last_name;
                        $payment_type = $payment->payment_type;
                        $payment_gateway = $payment->payment_gateway;
                        $bank = $payment->bank;
                        $check_number = $payment->check_number;
                        $bank_account_number = $payment->bank_account_number;
                        $or_number = $payment->or_number;
                        $pr_number = $payment->cr_number;
                        $record_type = $payment->record_type;
                        $gateway_ref_number = $payment->payment_gateway_reference_number;
                        $remarks = $payment->remarks;
                        $payment_encode_type = $payment->payment_encode_type;

                        if($payment_type == 'penalty') {
                            $penalty_details['first_name'] = $payment->first_name;
                            $penalty_details['last_name'] = $payment->last_name;
                            $penalty_details['payment_gateway'] = $payment->payment_gateway;
                            $penalty_details['bank'] = $payment->bank;
                            $penalty_details['check_number'] = $payment->check_number;
                            $penalty_details['bank_account_number'] = $payment->bank_account_number;
                            $penalty_details['or_number'] = $payment->or_number;
                            $penalty_details['pr_number'] = $payment->cr_number;
                            $penalty_details['record_type'] = $payment->record_type;
                            $penalty_details['payment_gateway_reference_number'] = $payment->payment_gateway_reference_number;
                            $penalty_details['remarks'] = $payment->remarks;
                            $penalty_details['payment_encode_type'] = $payment->payment_encode_type;
                            $penalty_details['paid_at'] = Carbon::parse($payment->paid_at);
                        }

                        if($payment_type == 'monthly_amortization_payment') {
                            $amortization_details['first_name'] = $payment->first_name;
                            $amortization_details['last_name'] = $payment->last_name;
                            $amortization_details['payment_gateway'] = $payment->payment_gateway;
                            $amortization_details['bank'] = $payment->bank;
                            $amortization_details['check_number'] = $payment->check_number;
                            $amortization_details['bank_account_number'] = $payment->bank_account_number;
                            $amortization_details['or_number'] = $payment->or_number;
                            $amortization_details['pr_number'] = $payment->cr_number;
                            $amortization_details['record_type'] = $payment->record_type;
                            $amortization_details['payment_gateway_reference_number'] = $payment->payment_gateway_reference_number;
                            $amortization_details['remarks'] = $payment->remarks;
                            $amortization_details['payment_encode_type'] = $payment->payment_encode_type;
                            $amortization_details['paid_at'] = Carbon::parse($payment->paid_at);
                        }


                        $bulk_payments [] = new Collection([
                            (string) $number, $paid_at, $amount, $client_number, $first_name, 
                            $last_name, $payment_type, $payment_gateway,
                            $bank, $check_number, $bank_account_number, $or_number, $pr_number, $record_type, 
                            $payment_amortization_id, $gateway_ref_number, $remarks, $payment_encode_type, $penalty_details, $amortization_details
                        ]);

                        $remaining_balance = 0;

                        $number++;
                    }
                }
            }

            if( $collection_recalculate || $re_recalculate ) {

                $included_amortization_id = [];
                $recalculate = [];
                $recalculate_label = [];
                foreach( $bulk_payments as $k => $value ) {
                    if( $k !== 0 ) {
                        if( in_array($value[14], $included_amortization_id) ) {
                            $map_counter = 0;
                            $included_amortization_id_count = count($included_amortization_id);
                            foreach( $included_amortization_id as $key => $row) {
                                if( $value[14] == $included_amortization_id[$key] ) {
                                    $recalculate[$key][1] = $value[1];
                                    $recalculate[$key][2] = $recalculate[$key][2] + $value[2];
                                    $recalculate[$key][6] = 'monthly_amortization_payment';
                                    $recalculate[$key][19] = $value[19];
                                }
                                if( $map_counter == $included_amortization_id_count ) {
                                    $included_amortization_id = [];
                                }
                                $map_counter++;
                            }
                        } else {
                            if($value[6] == 'penalty') {
                                $included_amortization_id[$k] = $value[14];
                            }
                            $recalculate[$k] = $value;
                        }
                    } else {
                        $recalculate_label[] = $value;
                    }
                }

                $recalculate = array_merge($recalculate_label, $recalculate);
                $bulk_payments = $recalculate;
            }
            
            // dd($bulk_payments);

            if( count($bulk_payments) > 1 ) {
                Reservation::where('reservation_number', $reservation_number)->update([
                    'recalculated' => 1
                ]);
                RealEstatePayment::whereIn('id', $lists_of_payments)->delete();
                AmortizationSchedule::where('reservation_number', $reservation_number)->where('is_sales', 0)->delete();
                AmortizationSchedule::where('reservation_number', $reservation_number)->update([
                    'date_paid' => NULL, 
                    'paid_status' => NULL, 
                    'amount_paid' => NULL, 
                    'transaction_id' => NULL, 
                    'pr_number' => NULL, 
                    'or_number' => NULL, 
                    'account_number' => NULL,
                    'remarks' => NULL,
                    'updated_at' => NULL,
                    'principal' => 0, 
                    'interest' => 0, 
                    'balance' => 0, 
                    'is_collection' => 1, 
                    'excess_payment' => 0,
                    'type' => NULL,
                ]);
                AmortizationPenalty::where('reservation_number', $reservation_number)->where('discount', 100)->delete();
                AmortizationPenalty::where('reservation_number', $reservation_number)->update([
                    'paid_at' => null,
                    'amount_paid' => null,
                ]);

                if( $this->waive_penalty && !is_null($request_status) ) {
                    AmortizationPenalty::where('reservation_number', $reservation_number)
                        ->whereNull('status')
                        ->whereNull('paid_at')
                        ->delete();
                }

                if( $this->waive_penalty && is_null($request_status) ) {
                    AmortizationPenalty::where('reservation_number', $reservation_number)
                        ->whereNull('status')
                        ->delete();
                }

                $rows = new Collection($bulk_payments);
                $import = new ReservationPaymentImport;
                $import->recompute = true;
                $import->waive_penalty = $this->waive_penalty;
                $import->user = is_null($request->user()) ? $request->user : $request->user();
                $import->collection($rows);
            }

        }

    }

    public function update_payment_detail(Request $request)
    {

        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('RealEstate.Update.PaymentDetails')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $value = $request->value;
        $id = $request->id;
        $field = $request->field;
        $is_verified = $request->is_verified;

        if( $field === 'client_number' ) {
            $recalculate = true;
            $params = [$field => $value];
            $reservation = Reservation::where($field, $value)->first();
            if( $reservation ) {
                if( !is_null($reservation->reservation_number) || $reservation->reservation_number !== '' ) {
                    $params['reservation_number'] = $reservation->reservation_number;
                }
            }
        }

        if( $field === 'reservation_number' ) {
            $params = [$field => $value,];
            $reservation = Reservation::where($field, $value)->first();
            if( $reservation ) {
                if( !is_null($reservation->client_number) || $reservation->client_number !== '' ) {
                    $recalculate = true;
                    $params['client_number'] = $reservation->client_number;
                }
            }
        }

        RealEstatePayment::where('id', $id)->update($params);
        
        $payment = RealEstatePayment::where('id', $id)
            ->with(['paymentStatuses' => function ($query) {
                $query->orderBy('created_at', 'DESC');
            }])
            ->with('reservation')
            ->with('verifiedBy')
            ->orderBy('created_at', 'DESC')
            ->limit(1)->get();

        if( $is_verified == 1 && $recalculate && ($payment[0]->payment_type == 'penalty' || $payment[0]->payment_type == 'monthly_amortization_payment') ) {
            Reservation::where($field, $value)->update([
                'recalculated' => 0
            ]);
        }

        RealestatePaymentActivityLog::create([
            'action' => 'update_' . $field,
            'description' => 'Transaction # ' . $payment[0]->transaction_id . ' ' . str_replace('_', ' ', $field) . ' update',
            'model' => 'App\Models\RealEstate\RealestatePaymentActivityLog',
            'properties' => null,
            'created_by' => $request->user()->id,
        ]);

        return $payment;
    }

    public function delete_payment_detail(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('RealEstate.Delete.PaymentDetails')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $payment = RealEstatePayment::where('id', $request->id)->limit(1)->first();

        if( $payment ) {

            if( !is_null($payment->cash_term_ledger_id) ) {
                CashTermPenalty::where('id', $payment->cash_term_ledger_id)->delete();
            }

            RealEstatePayment::where('id', $request->id)->delete();
            RealEstatePaymentStatus::where('transaction_id', $payment->transaction_id)->delete();

            $this->setReservationToRecalculate([
                'reservation_number' => $request->reservation_number,
                'payment_terms_type' => $request->payment_terms_type,
            ]);
            
            return true;
        } else {
            return response()->json(['message' => 'Payment details not exists.'], 400);
        }
    }

    public function setReservationToRecalculate($param)
    {
        if( $param['payment_terms_type'] == 'in_house' ) {

            $paid_amortization_count = AmortizationSchedule::where('reservation_number', $param['reservation_number'])
                ->where('is_collection', 1)
                ->whereNotNull('amount_paid')->get()->count();

            if( $paid_amortization_count > 0 ) {
                Reservation::where('reservation_number', $param['reservation_number'])->update([
                    'recalculated' => 0
                ]);
            }
        }
    }

    public static function getPaymentDetails($reservation, $order = 'DESC', $waive_penalty = false)
    {
        $reservation_number = $reservation->reservation_number;
        $client_number = $reservation->client_number;

        if( $client_number != '' ) {
            $query = RealEstatePayment::orderBy('id', $order);
            
            if( $waive_penalty ) {
                $query->where(function($q) use ($reservation_number, $client_number){
                    $q->where('reservation_number', $reservation_number)->orWhere('client_number', $client_number);
                })->whereNotNull('amortization_schedule_id')->with('paymentStatuses');
            } else {
                $query->where(function($query) use ($reservation_number, $client_number){
                    $query->where('reservation_number', $reservation_number)->orWhere('client_number', $client_number);
                })->with('paymentStatuses');
            }
            
                
        } else {

            $query = RealEstatePayment::orderBy('id', $order);
            if( $waive_penalty ) {
                $query->where('reservation_number', $reservation_number)
                    ->whereNotNull('amortization_schedule_id')
                    ->with('paymentStatuses');
            } else {
                $query->where('reservation_number', $reservation_number)
                    ->with('paymentStatuses');
            }
            
        }

        $payments = $query->get();

        return $payments;
    }

    public function update_amortization(Request $request)
    {
        $data = $request->data;
        $reservation_number = $data['reservation_number'];
        $amortization_id = $data['amortization_id'];
        $transaction_id = $data['transaction_id'];
        $paid_at = Carbon::parse($data['paid_at'])->setTimezone('Asia/Manila');
        $amount_paid = $data['amount_paid'];
        $cr_number = $data['cr_number'];
        $or_number = $data['or_number'];
        $payment_gateway = $data['payment_gateway'];
        $payment_gateway_reference_number = isset($data['payment_gateway_reference_number']) ? $data['payment_gateway_reference_number'] : null;
        $bank = isset($data['bank']) ? $data['bank'] : null;
        $check_number = isset($data['check_number']) ? $data['check_number'] : null;
        $bank_account_number = isset($data['bank_account_number']) ? $data['bank_account_number'] : null;
        $remarks = $data['remarks'];

        $payment = RealEstatePayment::where('reservation_number', $reservation_number)
            ->where('transaction_id', $transaction_id)->first();

        /**
         * Check if amount and date are updated
         * recompute the amortization schedule if either 2 are updated
         * Ignore recalculation on other detail update
         */
        $is_updated = false;

        if( date('Y-m-d', strtotime($paid_at)) !== date('Y-m-d', strtotime($payment->paid_at)) ) {
            $is_updated = true;
            AmortizationPenalty::where('reservation_number', $reservation_number)->delete();
        }

        if( (float) $amount_paid !== (float) $payment->payment_amount ) {
            $is_updated = true;
        }

        if( $is_updated ) {
            Reservation::where('reservation_number', $reservation_number)->update([
                'recalculated' => 0
            ]);
        }

        /**
         * Update payment details
         */
        
        RealEstatePayment::where('reservation_number', $reservation_number)
            ->where('transaction_id', $transaction_id)->update([
                'paid_at' => $paid_at,
                'payment_amount' => $amount_paid,
                'cr_number' => $cr_number,
                'or_number' => $or_number,
                'payment_gateway' => $payment_gateway,
                'payment_gateway_reference_number' => $payment_gateway_reference_number,
                'bank' => $bank,
                'bank_account_number' => $bank_account_number,
                'check_number' => $check_number,
                'remarks' => $remarks,
            ]);
         
        /**
         * Update amortization details
         */
        AmortizationSchedule::where('id', $amortization_id)->update([
            'date_paid' => $paid_at,
            'amount_paid' => $amount_paid,
            'pr_number' => $cr_number,
            'or_number' => $or_number,
            'account_number' => $bank_account_number,
            'remarks' => $remarks,
        ]);

        $reservation = Reservation::where('reservation_number', $reservation_number)->first();

        if( $reservation['recalculated'] === 0 ) {
            $Payment = new Payment;
            $request->collection_recalculate = true;
            $request->reservation_number = $reservation_number;
            $Payment->recompute_account($request);
        }

        $reservation['request_user_id'] = $request->user()->id;

        $amortization_schedule = AmortizationSchedule::collections($reservation);
        $reservation['amortization_collections'] = $amortization_schedule;

        $cash_ledger = CashTermLedger::collections($reservation);
        $reservation['cash_ledger_collections'] = $cash_ledger;

        $payments = Payment::getPaymentDetails($reservation);
        $reservation['payment_details'] = $payments;
        
        return $reservation->load('client.information')
            ->load('agent.team_member_of.team')
            ->load('co_buyers.details')
            ->load('promos')
            ->load('attachments')
            ->load('referrer')
            ->load('referrer_property_details')
            ->load(['amortization_schedule.penalties', 'amortization_schedule.payments'])
            ->load(['cash_term_ledger.penalties', 'cash_term_ledger.payments'])
            ->load('sales_manager')
            ->load('sales_director')
            ->load('amortization_fees.added_by');
        
    }

    public function update_rf_dp_details(Request $request)
    {
        // Request Details
        $recalculate = false;
        $update_details = false;
        $reservation_number = $request->reservation_number;
        $payment_terms_type = $request->payment_terms_type;
        $reservation_fee_date = Carbon::parse($request->reservation_date)->setTimezone('Asia/Manila');
        $reservation_fee_amount = $request->reservation_fee_amount;
        // $downpayment_percentage = $request->downpayment_percentage;
        $downpayment_amount = $request->downpayment_amount;
        $downpayment_due_date = Carbon::parse($request->downpayment_due_date)->setTimezone('Asia/Manila');
        $start_date = Carbon::parse($request->start_date)->setTimezone('Asia/Manila');
        $end_date = Carbon::parse($request->end_date)->setTimezone('Asia/Manila');
        $nsp_computed = $request->nsp_computed;
        $request_number_of_splits = $request->number_of_splits;
        $promos = $request->promos;

        // Reservation Details
        $reservation = Reservation::where('reservation_number', $reservation_number)->first();
        $total_balance_in_house = $reservation->total_balance_in_house;
        $factor_rate = $reservation->factor_rate;
        $split_downpayment = $reservation->split_downpayment;
        $number_of_downpayment_splits = $reservation->number_of_downpayment_splits;
        $split_cash = $reservation->split_cash;
        $number_of_cash_splits = $reservation->number_of_cash_splits;
        $number_of_years = $reservation->number_of_years;
        $interest_rate = getenv('FACTOR_PERCENTAGE') ? (float) env('FACTOR_PERCENTAGE') : 7;

        // Handling promos
        $existing_promos = [];
        $reservation_promos = ReservationPromo::where('reservation_number', $reservation_number)->get();

        foreach( $reservation_promos as $key => $promo ) {
            $existing_promos[$promo['id']] = $promo['promo_type'];
        }

        // Adding new promos
        foreach( $promos as $key => $promo ) {
            $is_exist_promo = in_array($promo, $existing_promos);
            if( !$is_exist_promo ) {
                ReservationPromo::create([
                    'reservation_number' => $reservation_number,
                    'promo_type' => $promo,
                ]);
            }
        }

        // Removing promos
        foreach( $existing_promos as $key => $promo ) {
            $is_exist_promo = in_array($promo, $promos);
            if( !$is_exist_promo ) {
                ReservationPromo::where('reservation_number', $reservation_number)
                ->where('id', $key)
                ->delete();
            }
        }

        // Update validations
        $number_of_splits_check = ( $payment_terms_type === 'in_house' ) ? 
            $reservation->number_of_downpayment_splits != $request_number_of_splits : 
            $reservation->number_of_cash_splits != $request_number_of_splits;
        $downpayment_amount_check = $reservation->downpayment_amount != $downpayment_amount;
        $downpayment_due_date_check = !Carbon::parse($reservation->downpayment_due_date)->eq($downpayment_due_date);
        $reservation_fee_amount_check = $reservation->reservation_fee_amount != $reservation_fee_amount;
        $reservation_fee_date_check = !Carbon::parse($reservation->reservation_fee_date)->eq($reservation_fee_date);

        if( $downpayment_amount_check || $downpayment_due_date_check || $reservation_fee_amount_check || $reservation_fee_date_check || $number_of_splits_check ) {
            $update_details = true;
        }

        if( $payment_terms_type === 'in_house' ) {

            if( $update_details ) {

                Reservation::where('reservation_number', $reservation_number)->update([
                    'reservation_fee_date' => $reservation_fee_date,
                    'reservation_fee_amount' => $reservation_fee_amount,
                    'downpayment_due_date' => $downpayment_due_date,
                    'downpayment_amount' => $downpayment_amount,
                    'split_downpayment_start_date' => $downpayment_due_date,
                    'split_downpayment_end_date' => $end_date,
                    'number_of_downpayment_splits' => $request_number_of_splits
                ]);
            
                if( $downpayment_amount_check || $downpayment_due_date_check || $reservation_fee_date_check || $number_of_splits_check ) {
                    $recalculate = true;

                    $updated_reservation = Reservation::where('reservation_number', $reservation_number)->first();

                    // $total_balance_in_house = $nsp_computed - $downpayment_amount;
                    $new_amortization_amount = round($updated_reservation->total_balance_in_house * $factor_rate, 2);

                    
                    if( $downpayment_amount_check ) {
                        AmortizationSchedule::where('reservation_number', $reservation_number)->update([
                            'amount' => $new_amortization_amount,
                        ]);
                    }

                    if( $downpayment_due_date_check || $reservation_fee_date_check || $number_of_splits_check ) {

                        if( $number_of_splits_check && $request_number_of_splits < $number_of_downpayment_splits ) {

                            $list_downpayment = RealEstatePayment::where('reservation_number', $reservation_number)
                                ->where('payment_type', 'downpayment')
                                ->orderBy('paid_at', 'DESC')->get();
    
                            
                            if( $list_downpayment ) {
    
                                $payment_count = $list_downpayment->count();
                                $counter = 0;
                                $remove_dp = false;
    
                                if( $payment_count >= $request_number_of_splits ) {
                                    $counter = $payment_count - $request_number_of_splits;
                                }
    
                                foreach( $list_downpayment as $key => $payment ) {
    
                                    if( $counter > 0 ) {
                                        RealEstatePayment::where('reservation_number', $reservation_number)
                                            ->where('payment_type', 'downpayment')
                                            ->where('id', $payment->id)->delete();
                                    }
                                    $counter--;
                                    
                                }
                               
                            }
                            
                        }

                        $amortization_to_record = [];
                        $monthly_amortization_due_date = $split_downpayment ? Carbon::parse($end_date)->addMonthsNoOverflow(1) : Carbon::parse($downpayment_due_date)->addMonthsNoOverflow(1);

                        Reservation::where('reservation_number', $reservation_number)->update([
                            'monthly_amortization_due_date' => $monthly_amortization_due_date,
                        ]);

                        $initial_date = $split_downpayment ? Carbon::parse($end_date)->setTimezone('Asia/Manila') : Carbon::parse($downpayment_due_date)->setTimezone('Asia/Manila');
                        $amortization_date = Carbon::parse($monthly_amortization_due_date)->setTimezone('Asia/Manila');
                        $amortization_date->hour = 0;
                        $amortization_date->minute = 0;
                        $amortization_date->second = 0;

                        $interest = ($total_balance_in_house * ($interest_rate / 100)) / 12;
                        $principal = $new_amortization_amount - $interest;
                        $balance = $total_balance_in_house - $principal;

                        for ($i = 0; $i <= ($number_of_years * 12); $i++) {

                            if ($initial_date->day > $amortization_date->day && $amortization_date->month != 2) {
                                if ($initial_date->day == 31 && in_array($amortization_date->month, [2, 4, 6, 9, 11])) {
                                    $amortization_date->setDay(30);
                                } else {
                                    $amortization_date->setDay($initial_date->day);
                                }
                            }
            
                            $amortization_to_record[$i] = [
                                'due_date' => $amortization_date,
                                'amount' => $new_amortization_amount,
                                'generated_principal' => $principal,
                                'generated_interest' => $interest,
                                'generated_balance' => $balance,
                            ];
            
                            $interest = ($balance * ($interest_rate / 100)) / 12;
                            $principal = $new_amortization_amount - $interest;
                            $balance = $balance - $principal;
            
                            $previous_date = Carbon::parse($amortization_to_record[$i]['due_date']);
                            
                            // If Jan, calculate february months
                            if ($previous_date->month == 1) {
                                if ($initial_date->day >= 29) {
                                    if ($previous_date->isLeapYear()) {
                                        $add_month = Carbon::create($previous_date->year, $previous_date->month + 1, 29);
                                    } else {
                                        $add_month = Carbon::create($previous_date->year, $previous_date->month + 1, 28);
                                    }
                                } else {
                                    $add_month = $previous_date->addMonthsNoOverflow(1);
                                }
                            } else {
                                $add_month = $previous_date->addMonthsNoOverflow(1);
                            }
            
                            $amortization_date = $add_month;
            
                        }

                        $schedules = AmortizationSchedule::where('reservation_number', $reservation_number)
                            ->where('is_sales', 1)->orderBy('number', 'ASC')->get();

                        foreach( $schedules as $key => $record ) {
                            AmortizationSchedule::where('reservation_number', $reservation_number)
                                ->where('id', $record['id'])
                                ->update($amortization_to_record[$key]);
                        }

                    }
                    
                }

                if( $recalculate ) {
                    Reservation::where('reservation_number', $reservation_number)->update([
                        'recalculated' => 0
                    ]);
                }

            }

        } else {

            if( $update_details ) {

                Reservation::where('reservation_number', $reservation_number)->update([
                    'reservation_fee_date' => $reservation_fee_date,
                    'reservation_fee_amount' => $reservation_fee_amount,
                    'downpayment_due_date' => $downpayment_due_date,
                    'split_cash_start_date' => $downpayment_due_date,
                    'split_cash_end_date' => $end_date,
                    'number_of_cash_splits' => $request_number_of_splits
                ]);

                $updated_reservation = Reservation::where('reservation_number', $reservation_number)->first();

                $splits = ($split_cash) ? $number_of_cash_splits : 1;

                $initial_date = $split_cash ? Carbon::parse($end_date)->setTimezone('Asia/Manila') : Carbon::parse($reservation_fee_date)->addMonth()->setTimezone('Asia/Manila');
                $cash_term_date = $split_cash ? Carbon::parse($start_date)->setTimezone('Asia/Manila') : Carbon::parse($reservation_fee_date)->addMonth()->setTimezone('Asia/Manila');
                $cash_term_date->hour = 0;
                $cash_term_date->minute = 0;
                $cash_term_date->second = 0;
                $cash_term_data = [];

                for( $i = 0; $i < $request_number_of_splits; $i++ ) {

                    if ($initial_date->day > $cash_term_date->day && $cash_term_date->month != 2) {
                        if ($initial_date->day == 31 && in_array($cash_term_date->month, [2, 4, 6, 9, 11])) {
                            $cash_term_date->setDay(30);
                        } else {
                            $cash_term_date->setDay($initial_date->day);
                        }
                    }
    
                    $cash_term_data[$i] = [
                        'due_date' => $cash_term_date,
                        'amount' => $split_cash ? $updated_reservation['split_payment_amount'] : $updated_reservation['total_amount_payable']
                    ];
    
                    $previous_date = Carbon::parse($cash_term_data[$i]['due_date']);
                    
                    // If Jan, calculate february months
                    if ($previous_date->month == 1) {
                        if ($initial_date->day >= 29) {
                            if ($previous_date->isLeapYear()) {
                                $add_month = Carbon::create($previous_date->year, $previous_date->month + 1, 29);
                            } else {
                                $add_month = Carbon::create($previous_date->year, $previous_date->month + 1, 28);
                            }
                        } else {
                            $add_month = $previous_date->addMonthsNoOverflow(1);
                        }
                    } else {
                        $add_month = $previous_date->addMonthsNoOverflow(1);
                    }
    
                    $cash_term_date = $add_month;

                }

                $cash_ledger = CashTermLedger::where('reservation_number', $reservation_number)->orderBy('number', 'ASC')->get();
                $cash_ledger_count = $cash_ledger->count();
                $last_number = 0;
                $counter = 0;
                $included_ids = [];

                foreach( $cash_term_data as $key => $data ) {
                    $counter++;
                    if( isset($cash_ledger[$key]) ) {

                        CashTermLedger::where('reservation_number', $reservation_number)
                            ->where('id', $cash_ledger[$key]['id'])
                            ->update($cash_term_data[$key]);
                        $last_number = $cash_ledger[$key]['number'];
                        $included_ids[] = $cash_ledger[$key]['id'];

                    } else {

                        if( $cash_ledger_count < $counter && $request_number_of_splits > $cash_ledger_count ) {
                            $last_number = $last_number + 1;
                            $data['reservation_number'] = $reservation_number;
                            $data['number'] = $last_number;
                            $data['date_paid'] = null;
                            $data['amount_paid'] = null;
                            $data['remarks'] = null;
                            $data['datetime'] = null;
                            $data['created_at'] = Carbon::now()->setTimezone('Asia/Manila');
                            $create = CashTermLedger::create($data);
                            $included_ids[] = $create['id'];
                        } 

                    }
                }

                if( $request_number_of_splits < $cash_ledger_count ) {
                    
                    $ledger_to_delete = CashTermLedger::where('reservation_number', $reservation_number)
                        ->whereNotIn('id', $included_ids)->get();

                    foreach( $ledger_to_delete as $key => $record ) {
                        $transaction_id_to_delete = $record['transaction_id'];
                        $record_id_to_delete = $record['id'];

                        RealEstatePayment::where('reservation_number', $reservation_number)
                            ->where('transaction_id', $transaction_id_to_delete)->delete();

                        CashTermLedger::where('reservation_number', $reservation_number)
                            ->where('id', $record_id_to_delete)->delete();
                    }

                    
                }

            }
        }
    }

    public function reset_ra_payments(Request $request)
    {
        $reservation_number = $request->reservation_number;
        $client_number = $request->client_number;
        AmortizationSchedule::where('reservation_number', $reservation_number)
            ->where('is_sales', 0)
            ->delete();
        AmortizationSchedule::where('reservation_number', $reservation_number)->update([
            'date_paid' => NULL, 
            'paid_status' => NULL, 
            'amount_paid' => NULL, 
            'transaction_id' => NULL, 
            'pr_number' => NULL, 
            'or_number' => NULL, 
            'account_number' => NULL,
            'remarks' => NULL,
            'updated_at' => NULL,
            'principal' => 0, 
            'interest' => 0, 
            'balance' => 0, 
            'is_collection' => 1, 
            'excess_payment' => 0,
            'type' => NULL,
        ]);
        AmortizationPenalty::where('reservation_number', $reservation_number)->delete();
        RealEstatePayment::where('reservation_number', $reservation_number)->delete();
        RealEstatePayment::where('client_number', $client_number)->delete();
        RealestateActivityLog::where('reservation_number', $reservation_number)->delete();
        

        dd($reservation_number, $client_number);

    }
}