<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SalesAdminPortal\Payment;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\RealEstate\RealEstatePayment;
use App\Models\RealEstate\RealEstatePaymentStatus;

use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\AmortizationSchedule;
use App\Models\RealEstate\AmortizationPenalty;
use App\Models\RealEstate\CashTermLedger;
use App\Models\RealEstate\CashTermPenalty;
use App\Models\RealEstate\RealestateActivityLog;

use App\Http\Requests\RealEstate\AddPaymentRequest;

use Carbon\Carbon;

class AddPayment extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(AddPaymentRequest $request)
    {
        //
        $user_info = (!isset($request->bulk_upload_request) ) ? $request->user() : $request->user;

        if (!$user_info->hasRole(['super-admin'])) {
            if ( 
                $user_info->user_type != 'admin' ||
                !$user_info->hasPermissionTo('SalesAdminPortal.AddPayment.AmortizationLedger')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $disabled = false;
        $generateTransactionID = Str::upper(Str::random(10));

        // Creates a new reference number if it encounters duplicate
        while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
            $generateTransactionID = Str::upper(Str::random(10));
        }

        if( !is_null($request->reservation_number) ) {
            $reservation = Reservation::where('reservation_number', $request->reservation_number)
                ->with('client.information')
                ->with('agent')
                ->with('sales_manager')
                ->with(['amortization_schedule' => function($q){
                    $q->orderBy('number', 'ASC')->orderBy('id', 'ASC');
                }, 'amortization_schedule.penalties', 'amortization_schedule.payments'])
                ->with(['cash_term_ledger' => function($q){
                    $q->whereNull('paid_status');
                }, 'cash_term_ledger.penalties'])
                ->first();
            $getPaymentDetails = Payment::getPaymentDetails($reservation);
            $reservation['payment_details'] = $getPaymentDetails;
        } else {

            $newPayment = $this->new_payment_transaction($generateTransactionID, false, $request, false, false, $user_info);
            return true;

        }

        // return $reservation;

        // handling of agent and sales manager fields, If client do not have sales agent or sales manager set to empty string
        $sales_agent_name = (isset($reservation->agent->first_name)) ? $reservation->agent->first_name." ".$reservation->agent->last_name : '';
        $sales_manager_name = (isset($reservation->sales_manager->first_name)) ? $reservation->sales_manager->first_name." ".$reservation->sales_manager->last_name : '';

        /**
         * Save transaction
         */
        if( !isset($request->re_dashboard_request) ) {
            $newPayment = $this->new_payment_transaction($generateTransactionID, $reservation, $request, $sales_agent_name, $sales_manager_name, $user_info);
        } else {
            // Bypass new payment creation via RE dashboard
            $newPayment = $request->re_dashboard_request;
            $generateTransactionID = $request->transaction_id;
        }

        //
        if( in_array($request->payment_type, ['reservation_fee_payment', 'title_fee', 'retention_fee']) ) {

            RealEstatePayment::where('transaction_id', $generateTransactionID)->update([
                'payment_amount' => 0,
                'paid_at' => $request->paid_at
            ]);

            switch ($request->payment_type) {
                case 'reservation_fee_payment':
                    $payment_type_amount = $reservation->reservation_fee_amount;
                    $payment_label = 'Reservation Fee';
                    break;
                case 'title_fee':
                    if( $reservation->with_twelve_percent_vat ) {
                        $payment_type_amount = $reservation->net_selling_price_with_vat * 0.05;
                    } else {
                        $payment_type_amount = $reservation->net_selling_price * 0.05;
                    }
                    $payment_label = 'Title Fee';
                    break;
                case 'retention_fee':
                    $payment_type_amount = ($reservation->with_five_percent_retention_fee) ? round($reservation->retention_fee, 2) : 0;
                    $payment_label = 'Retention Fee';
                    break;
                default:
                    break;
            }
            
            $new_transaction = false;
            $already_paid = false;
            $with_payment_type = false;

            foreach( $reservation->payment_details as $detail ) {

                if( $detail->payment_type === $request->payment_type ) {

                    $with_payment_type = true;

                    $request->payment_amount =  $request->payment_amount + $detail->payment_amount;

                    if( $detail->payment_amount < $payment_type_amount ) {

                        $new_payment_type_amount = ($request->payment_amount >= $payment_type_amount) ? $payment_type_amount : $request->payment_amount;

                        if(  $detail->payment_amount <= 0 ) {
                            RealEstatePayment::where('transaction_id', $generateTransactionID)->update([
                                'payment_amount' => $new_payment_type_amount,
                            ]);
                            $new_transaction = true;
                        } else {
                            RealEstatePayment::where('transaction_id', $detail->transaction_id)->update([
                                'payment_amount' => $new_payment_type_amount,
                            ]);
                            $new_transaction = false;

                            if(  $reservation->payment_terms_type == 'in_house' ) {
                                Reservation::where('reservation_number', $reservation->reservation_number)->update([
                                    'recalculated' => 0]
                                );
                            }
                        }

                        break;
                    } else {
                        $already_paid = true;
                        break;
                    }
                }
            }

            if( !$with_payment_type ) {
                RealEstatePayment::where('transaction_id', $generateTransactionID)->update([
                    'payment_amount' => $request->payment_amount,
                ]);
            }
            
            if( !$new_transaction && $with_payment_type ) {
                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                if( !isset($request->bulk_upload_request) && $already_paid ) {
                    return response()->json(['message' => $payment_label . ' is already paid.'], 400);
                }
            } else {
                $PaymentController = new Payment;
                $PaymentController->setReservationToRecalculate([
                    'reservation_number' => $reservation->reservation_number,
                    'payment_terms_type' => $reservation->payment_terms_type,
                ]);
            }
        }

        // Downpayment
        // Transact payment as is, if in last downpayment add to last split the payment
        if( in_array($request->payment_type, ['downpayment']) ) {

            $downpayment_details = [];
            $number_of_splits = ($reservation->split_downpayment) ? $reservation->number_of_downpayment_splits : 1;
            $paid_downpayment = 0;
            $paid_downpayment_amount = $reservation->reservation_fee_amount;
            $downpayment_transaction_id = false;
            $existing_downpayment_amount = 0;

            foreach( $reservation->payment_details as $detail ) {
                if( $detail->payment_type === 'downpayment' ) {
                    $downpayment_details[] = $detail;
                }
            }

            $downpayment_details = array_reverse($downpayment_details);

            foreach( $downpayment_details as $detail ) {
                if( $detail->payment_type === 'downpayment' ) {
                    $paid_downpayment++;
                    $paid_downpayment_amount = $paid_downpayment_amount + $detail->payment_amount;
                    if( $number_of_splits == $paid_downpayment ) {
                        $downpayment_transaction_id = $detail->transaction_id;
                        $existing_downpayment_amount = $detail->payment_amount;
                    }
                }
            }

            if( $paid_downpayment_amount >= $reservation->downpayment_amount ) {
                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                if( !isset($request->bulk_upload_request) ) {
                    return response()->json(['message' => 'Downpayment is already paid.'], 400);
                }
            } else {
                $PaymentController = new Payment;
                $PaymentController->setReservationToRecalculate([
                    'reservation_number' => $reservation->reservation_number,
                    'payment_terms_type' => $reservation->payment_terms_type,
                ]);
            }

            if( $downpayment_transaction_id !== false ) {
                RealEstatePayment::where('transaction_id', $downpayment_transaction_id)->update([
                    'payment_amount' => $request->payment_amount + $existing_downpayment_amount,
                    'paid_at' => $request->paid_at
                ]);
                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
            }
        }


        // Distribute large payment in every downpayment splits
        // Disabled | retain incase admin want to implement this approach
        if( in_array($request->payment_type, ['downpayment']) && $disabled ) {

            RealEstatePayment::where('transaction_id', $generateTransactionID)->update([
                'payment_amount' => 0,
                'paid_at' => $request->paid_at
            ]);

            $number_of_splits = ($reservation->split_downpayment) ? $reservation->number_of_downpayment_splits : 1;
            $downpayment_amount = ($reservation->split_downpayment) ? round($reservation->split_downpayment_amount, 2) : round($reservation->downpayment_amount, 2);
            $downpayment_paid_count = 0;
            $total_down_payment_paid_amount = 0;
            $total_downpayment_balance = 0;
            $loop_mapper = 0;

            foreach( $reservation->payment_details as $detail ) {
                if( $detail->payment_type === 'downpayment' ) {
                    if( $detail->payment_amount >= $downpayment_amount ) {
                        $number_of_splits--;
                    } else {
                        $total_downpayment_balance = $total_downpayment_balance + $detail->payment_amount;
                    }

                    $total_down_payment_paid_amount = $total_down_payment_paid_amount + $detail->payment_amount;
                }
            }

            $request->payment_amount =  $total_downpayment_balance + $request->payment_amount;

            if( $number_of_splits > 0 ) {

                for( $i = 0; $i < $number_of_splits; $i++ ) {

                    $new_transaction = false;
                    $loop_mapper++;

                    $reservation_updated = Reservation::where('reservation_number', $request->reservation_number)
                        ->with('payment_details')->first();

                    foreach( $reservation_updated->payment_details as $detail ) {

                        if( $detail->payment_type === 'downpayment' ) {

                            if( $detail->payment_amount >= $downpayment_amount ) {
                                $downpayment_paid_count++;
                            }

                            if( $detail->payment_amount < $downpayment_amount ) {

                                $downpayment = ($request->payment_amount >= $downpayment_amount) ? $downpayment_amount : $request->payment_amount;

                                if( $number_of_splits === $loop_mapper && $request->payment_amount > $downpayment_amount  ) {
                                    $downpayment = $request->payment_amount;
                                }

                                if(  $detail->payment_amount <= 0 ) {
                                    RealEstatePayment::where('transaction_id', $generateTransactionID)->update([
                                        'payment_amount' => $downpayment,
                                    ]);
                                    $new_transaction = true;
                                } else {
                                    RealEstatePayment::where('transaction_id', $detail->transaction_id)->update([
                                        'payment_amount' => $downpayment,
                                    ]);
                                    $new_transaction = false;
                                }

                                $request->payment_amount = $request->payment_amount - $downpayment;

                                if( $request->payment_amount < 1 ) {
                                    break;
                                }
                            }

                        }
                    }

                    if( $request->payment_amount < 1 ) {

                        if( !$new_transaction ) {
                            RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                        }

                        break;
                    }

                    if( $new_transaction )  {
                        $generateTransactionID = Str::upper(Str::random(10));
                        while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
                            $generateTransactionID = Str::upper(Str::random(10));
                        }
                        $request->new_payment_amount = 0;
                        $this->new_payment_transaction($generateTransactionID, $reservation, $request, $sales_agent_name, $sales_manager_name, $user_info);
                        unset($request->new_payment_amount);
                    }
                }
                    
            } else {
                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                if( !isset($request->bulk_upload_request) ) {
                    return response()->json(['message' => 'Downpayment is already paid.'], 400);
                }
            }

        }

        // Cash Terms Payment
        if( in_array($request->payment_type, ['split_cash', 'partial_cash', 'full_cash']) ) {

            $amount_payable = $reservation->total_amount_payable;
            $updated_cash_ledger = CashTermLedger::where('reservation_number', $request->reservation_number);
            $total_cash_term_paid = collect($updated_cash_ledger->get())->sum('amount_paid');
            $diff = $amount_payable - $total_cash_term_paid;
            if( $diff < 1 ) {
                $total_cash_term_paid = round($total_cash_term_paid);
            }

            if( $total_cash_term_paid >= $amount_payable ) {
                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                if( !isset($request->bulk_upload_request) ) {
                    return response()->json(['message' => 'Cash term is already paid.'], 400);
                }
            }

            if( $request->payment_type == 'split_cash' ) {

                $cash_ledger_count = $reservation->cash_term_ledger->count();
                $cash_ledger_counter = 0;
                $record_number = 0;
                $last_record_due_date = null;
                $record_amount_due = 0;

                if( $cash_ledger_count <= 0 ) {
                    RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                    if( !isset($request->bulk_upload_request) ) {
                        return response()->json(['message' => 'Cash term is already paid.'], 400);
                    }
                }

                foreach( $reservation->cash_term_ledger as $key => $record ) {

                    if( $record['paid_status'] === null) {

                        $cash_ledger_counter++;
                        $record_number = $record['number'];
                        $last_record_due_date = $record['due_date'];
                        $record_amount_due = $record['amount'];

                        $cash_record_amount_paid = is_null($record['amount_paid']) ? 0 : $record['amount_paid'];
                        $cash_total_amount_paid = $request->payment_amount + $cash_record_amount_paid;
                        $cash_remaining_payment_amount = ( $cash_total_amount_paid > $record['amount'] ) ? $cash_total_amount_paid - $record['amount'] : 0;

                        if( $cash_total_amount_paid > $record['amount'] && $cash_ledger_counter < $cash_ledger_count ) {
                            $record_amount = $record['amount'];
                        } else {
                            $record_amount = $cash_total_amount_paid;
                        }

                        if( is_null($record['amount_paid']) ) {

                            // $cash_paid_status = ( $cash_total_amount_paid < $record['amount'] ) ? null : 'completed';
                            $cash_paid_status = 'completed';
                            $cash_payment_amount = ( $cash_total_amount_paid < $record['amount'] ) ? $cash_total_amount_paid : $record_amount;
                            $cash_transaction_id = $generateTransactionID;

                            RealEstatePayment::where('transaction_id', $cash_transaction_id)->update([
                                'payment_amount' => $cash_payment_amount,
                                'paid_at' => $request->paid_at
                            ]);

                            $create_new_transaction = true;

                        } else {
                            // $cash_paid_status = ( $cash_total_amount_paid < $record['amount'] ) ? null : 'completed';
                            $cash_paid_status = 'completed';
                            $cash_payment_amount = ( $cash_total_amount_paid < $record['amount'] ) ? $cash_total_amount_paid : $record_amount;
                            $cash_transaction_id = $record['transaction_id'];

                            if( $cash_ledger_counter >= $cash_ledger_count ) {
                                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                                $cash_payment_amount = $cash_total_amount_paid;
                            }

                            RealEstatePayment::where('transaction_id', $cash_transaction_id)->update([
                                'payment_amount' => $cash_payment_amount,
                                'paid_at' => $request->paid_at
                            ]);

                            if( $record['amount_paid'] < $record['amount'] && $cash_remaining_payment_amount <= 0 ) {
                                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                            }

                            $create_new_transaction = false;
                        }

                        CashTermLedger::where('id', $record['id'])
                        ->update([
                            'transaction_id' => $cash_transaction_id,
                            'date_paid' => $request->paid_at,
                            'paid_status' => $cash_paid_status,
                            'amount_paid' => $cash_payment_amount,
                            'pr_number' => $request->pr_number,
                            'or_number' => $request->or_number,
                            'remarks' => $request->remarks,
                        ]);
                    
                        if( isset($request->re_dashboard_request) ) {
                            RealEstatePayment::where('id', $request->payment_id)->update([
                                'cash_term_ledger_id' => $record['id'],
                                'reservation_number' => $record['reservation_number']
                            ]);
                        }

                        if( $cash_remaining_payment_amount <= 0) {
                            break;
                        } else {
                            $request->payment_amount = $cash_remaining_payment_amount;

                            if( $create_new_transaction ) {
                                $generateTransactionID = Str::upper(Str::random(10));
                                while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
                                    $generateTransactionID = Str::upper(Str::random(10));
                                }
                                $request->new_payment_amount = $request->payment_amount;
                                $this->new_payment_transaction($generateTransactionID, $reservation, $request, $sales_agent_name, $sales_manager_name, $user_info);
                                unset($request->new_payment_amount);
                            }
                            
                        }

                    }

                }

            } else {
                foreach( $reservation->cash_term_ledger as $key => $record ) {

                    $field_checker = ($reservation->split_cash == 1) ? $record['amount_paid'] : $record['paid_status'];

                    if( $field_checker === null) {
                        
                        $total_amount_paid = (!is_null($record['amount_paid'])) ? $record['amount_paid'] : 0;
                        $record_number = $record['number'];
                        $last_record_due_date = $record['due_date'];
                        $record_amount_due = $record['amount'];

                        if( $total_amount_paid <= 0 ) {
                            $cash_payment_amount = $request->payment_amount;
                            $cash_paid_status = ( $request->payment_amount < $amount_payable ) ? null : 'completed';
                            $cash_transaction_id = $generateTransactionID;
                            $initial_payment = true;
                        } else {
                            $cash_payment_amount = ($reservation->split_cash == 1) ? $request->payment_amount : $total_amount_paid + $request->payment_amount;
                            $cash_paid_status = ( ($request->payment_amount + $total_amount_paid ) < $amount_payable ) ? null : 'completed';
                            $cash_transaction_id = $record['transaction_id'];
                            $initial_payment = false;
                        }

                        if( $reservation->split_cash == 1 ) {
                            $cash_paid_status =  'completed';
                        } else {

                            RealEstatePayment::where('transaction_id', $cash_transaction_id)->update([
                                'payment_amount' => $cash_payment_amount,
                                'paid_at' => $request->paid_at
                            ]);

                            if( !$initial_payment ) {
                                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                            }
                            
                        }

                        CashTermLedger::where('id', $record['id'])
                        ->update([
                            'transaction_id' => $cash_transaction_id,
                            'date_paid' => $request->paid_at,
                            'paid_status' => $cash_paid_status,
                            'amount_paid' => $cash_payment_amount,
                            'pr_number' => $request->pr_number,
                            'or_number' => $request->or_number,
                            'remarks' => $request->remarks,
                        ]);
                    
                        if( isset($request->re_dashboard_request) ) {
                            RealEstatePayment::where('id', $request->payment_id)->update([
                                'cash_term_ledger_id' => $record['id'],
                                'reservation_number' => $record['reservation_number']
                            ]);
                        }
                        
                        // Stop the process on first null paid status
                        break;
                    } else {
                        if( $reservation->split_cash == 0 ) {
                            RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                        }
                    }

                }
            }

            

            $updated_cash_ledger = CashTermLedger::where('reservation_number', $request->reservation_number);
            $total_cash_term_paid = collect($updated_cash_ledger->get())->sum('amount_paid');
            $last_number = $updated_cash_ledger->orderBy('number', 'DESC')->first();
            $diff = $amount_payable - $total_cash_term_paid;
            
            if( $diff < 1 ) {
                $total_cash_term_paid = round($total_cash_term_paid);
            }

            if( $total_cash_term_paid < $amount_payable && $record_number >= $last_number->number && $reservation->split_cash == 1 ) {
                CashTermLedger::where('reservation_number', $request->reservation_number)->create([
                    'reservation_number' => $request->reservation_number,
                    'number' => $record_number + 1,
                    'due_date' => Carbon::parse($last_record_due_date)->addMonth(1),
                    'amount' => $record_amount_due,
                    'date_paid' => null,
                    'amount_paid' => null,
                    'remarks' => null,
                    'datetime' => null,
                    'created_at' => Carbon::now()->setTimezone('Asia/Manila')
                ]);

                Reservation::where('reservation_number', $request->reservation_number)->update([
                    'number_of_cash_splits' => $record_number + 1
                ]);
            }
        }

        // Amortization Schedule Payment
        $schedule_count = 0;
        $id_lists = [];
        $amount_due = 0;
        $first_date_paid = false;
        $last_date_paid = false;
        $paid_key = null;
        $data_counter = 0;
        $penaltyComputed = 0;
        $recalculation_data = [];
        $recalculate = false;
        $amortization_due_dates = [];
        $amortization_payments = [];
        $amotization_request_paid_at_added = false;
        $arrange_counter = 0;
        $tansactions_lists = [];

        if( ($request->payment_type === 'monthly_amortization_payment' || $request->payment_type === 'penalty') && $reservation->payment_terms_type == 'in_house' ) {

            foreach($reservation->amortization_schedule as $key => $record) {
                if( $record['is_collection'] ) {
                    $schedule_count++;
                    $id_lists[] = $record['id'];
                    $amount_due = $record['amount'];

                    if( $first_date_paid === false && $record['date_paid'] !== null ) {
                        $first_date_paid = Carbon::parse($record['due_date']);
                    }

                    if( $record['date_paid'] !== null ) {
                        $last_date_paid = Carbon::parse($record['due_date']);
                        $paid_key = $key;
                    }

                    // Handling of automated penalty on bulk upload
                    if( isset($request->recalculate_request) || isset($request->recompute) ) {
                        $penaltyComputed = AmortizationSchedule::automate_penalty($record, [
                            'date_to_check' => (  $record['date_paid'] !== null ) ? Carbon::parse($record['date_paid']) : Carbon::now(),
                            'penaltyComputed' => $penaltyComputed,
                            'user_id' => $user_info->id
                        ]);
                    }


                    if( !is_null($record['date_paid']) && $record['excess_payment'] == 0 && !isset($request->recalculate_request) ) {     

                        $next_record = AmortizationSchedule::where('reservation_number', $reservation->reservation_number)
                            ->where('id', '>', $record['id'])
                            ->where('is_collection', 1)
                            ->where('excess_payment', 1)
                            ->first();

                        $nextDueDate = $this->get_next_due_date($record['due_date']);
                        $start_date = Carbon::parse($request->paid_at)->startOfDay()->gte(Carbon::parse($record['date_paid'])->startOfDay());

                        if( $next_record ) {
                            $end_date = Carbon::parse($request->paid_at)->startOfDay()->lt($next_record->due_date);
                        } else {
                            $end_date = false;
                        }

                        if( Carbon::parse($request->paid_at)->lte($record['date_paid']) && !$start_date && $amotization_request_paid_at_added === false ) {

                            $amotization_request_paid_at_added = true;
                            $recalculate = true;

                            // $request_date_paid = Carbon::parse($request->paid_at)->startOfDay()->lt($record['date_paid']) ? $record['date_paid'] : $request->paid_at

                            $recalculation_data[$arrange_counter] = [ 
                                'record_id' => 'newly added',
                                'bulk_upload_request' => true,
                                'recalculate_request' => true, 
                                'reservation_number' => $request->reservation_number,
                                'transaction_id' => $record['transaction_id'],
                                'payment_amount' => $request->payment_amount, 
                                'payment_gateway' => $request->payment_gateway,
                                'payment_type' => $request->payment_type,
                                'remarks' => $request->remarks,
                                'pr_number' => $request->pr_number,
                                'or_number' => $request->or_number,
                                'bank' => $request->bank,
                                'bank_account_number' => $request->bank_account_number,
                                'check_number' => $request->check_number,
                                'record_type' => !isset($request->payment_form) ? 'bulk_upload' : null,
                                'payment_gateway_reference_number' => $request->payment_gateway_reference_number,
                                'payment_encode_type' => $request->payment_encode_type,
                                'date_paid' => date('Y-m-d', strtotime($request->paid_at)),
                                'paid_at' => Carbon::parse($request->paid_at), 
                                'user' => $user_info,
                            ];

                            $arrange_counter++;

                        }

                        if( $record->payments->count() > 0 ) {

                            $additional_payment = 0;
                            $advance_payment = 0;
                            if( $start_date && $end_date && $amotization_request_paid_at_added === false ) {
                                $recalculate = true;
                                $amotization_request_paid_at_added = true;
                                $additional_payment = $request->payment_amount;
                                $advance_payment = (isset($request->payment_form) && isset($request->advance_payment) ) ? $request->advance_payment : 0;
                            }

                            $total_payment = collect($record->payments)->sum('payment_amount');
                            
                            foreach($record->payments as $k => $payment) {

                                $tansactions_lists[] = $payment['transaction_id'];

                                if( $payment['payment_type'] == 'penalty' ) {
                                    
                                    if( $next_record ) {
                                        // Check if there are online penalty transaction
                                        $other_penalties = RealEstatePayment::where('paid_at', '<', date('Y-m-d 00:00:00', strtotime($next_record->due_date)))
                                            ->where('paid_at', '>=', date('Y-m-d 00:00:00', strtotime($record['date_paid'])))
                                            ->where('is_verified', 1)
                                            ->whereNull('amortization_schedule_id')->where('payment_type', 'penalty')->get();

                                        if( $other_penalties ) {
                                            $total_other_penalties = collect($other_penalties)->sum('payment_amount');
                                            $total_payment = ($total_payment - $additional_payment) + $total_other_penalties;
                                        }
                                    }

                                }

                                if( $payment['payment_type'] == 'monthly_amortization_payment' ) {

                                    $recalculation_data[$arrange_counter]['record_id'] = $record['id'];
                                    $recalculation_data[$arrange_counter]['bulk_upload_request'] = true;
                                    $recalculation_data[$arrange_counter]['recalculate_request'] = true;
                                    $recalculation_data[$arrange_counter]['transaction_id'] = $payment['transaction_id'];
                                    $recalculation_data[$arrange_counter]['reservation_number'] = $payment['reservation_number'];
                                    $recalculation_data[$arrange_counter]['payment_amount'] = $total_payment + $additional_payment;
                                    $recalculation_data[$arrange_counter]['payment_gateway'] = $payment['payment_gateway'];
                                    $recalculation_data[$arrange_counter]['payment_type'] = $payment['payment_type'];
                                    $recalculation_data[$arrange_counter]['remarks'] = $payment['remarks'];
                                    $recalculation_data[$arrange_counter]['pr_number'] = $payment['pr_number'];
                                    $recalculation_data[$arrange_counter]['or_number'] = $payment['or_number'];
                                    $recalculation_data[$arrange_counter]['bank'] = $payment['bank'];
                                    $recalculation_data[$arrange_counter]['bank_account_number'] = $payment['bank_account_number'];
                                    $recalculation_data[$arrange_counter]['check_number'] = $payment['check_number'];
                                    $recalculation_data[$arrange_counter]['payment_gateway_reference_number'] = $payment['payment_gateway_reference_number'];
                                    $recalculation_data[$arrange_counter]['payment_encode_type'] = $payment['payment_encode_type'];
                                    $recalculation_data[$arrange_counter]['payment_date'] = date('Y-m-d', strtotime($payment['paid_at']));
                                    $recalculation_data[$arrange_counter]['record_type'] = $payment['record_type'];
                                    $recalculation_data[$arrange_counter]['advance_payment'] = $advance_payment;
                                    $recalculation_data[$arrange_counter]['paid_at'] = Carbon::parse($payment['paid_at']);
                                    $recalculation_data[$arrange_counter]['user'] = $user_info;
    
                                    $arrange_counter++;
                                }
                                
                            }
                        }
                        
                    } else {

                        if( isset($request->recompute) && !isset($request->waive_penalty) ) {

                            if( $record->payments->count() > 0 ) {

                                $next_record = AmortizationSchedule::where('reservation_number', $reservation->reservation_number)
                                    ->where('id', '>', $record['id'])
                                    ->where('is_collection', 1)
                                    ->where('excess_payment', 1)
                                    ->first();

                                $nextDueDate = $this->get_next_due_date($record['due_date']);
                                $start_date = Carbon::parse($request->paid_at)->startOfDay()->gte(Carbon::parse($record['date_paid'])->startOfDay());
                                
                                if( $next_record ) {
                                    $end_date = Carbon::parse($request->paid_at)->startOfDay()->lt($next_record->due_date);
                                } else {
                                    $end_date = false;
                                }

                                $additional_payment = 0;
                                $recalculate = true;
    
                                $total_payment = collect($record->payments)->sum('payment_amount');
                                
                                foreach($record->payments as $k => $payment) {
    
                                    $tansactions_lists[] = $payment['transaction_id'];
    
                                    if( $payment['payment_type'] == 'penalty' ) {

                                        if( $next_record ) {
                                            // Check if there are online penalty transaction
                                            $other_penalties = RealEstatePayment::where('paid_at', '<', date('Y-m-d 00:00:00', strtotime($next_record->due_date)))
                                                ->where('paid_at', '>=', date('Y-m-d 00:00:00', strtotime($record['date_paid'])))
                                                ->where('is_verified', 1)
                                                ->whereNull('amortization_schedule_id')->where('payment_type', 'penalty')->get();

                                            if( $other_penalties ) {
                                                $total_other_penalties = collect($other_penalties)->sum('payment_amount');
                                                $total_payment = ($total_payment - $additional_payment) + $total_other_penalties;
                                            }
                                        }

                                    }
    
                                    if( $payment['payment_type'] == 'monthly_amortization_payment' ) {
    
                                        $recalculation_data[$arrange_counter]['record_id'] = $record['id'];
                                        $recalculation_data[$arrange_counter]['bulk_upload_request'] = true;
                                        $recalculation_data[$arrange_counter]['recalculate_request'] = true;
                                        $recalculation_data[$arrange_counter]['transaction_id'] = $payment['transaction_id'];
                                        $recalculation_data[$arrange_counter]['reservation_number'] = $payment['reservation_number'];
                                        $recalculation_data[$arrange_counter]['payment_amount'] = $total_payment + $additional_payment;
                                        $recalculation_data[$arrange_counter]['payment_gateway'] = $payment['payment_gateway'];
                                        $recalculation_data[$arrange_counter]['payment_type'] = $payment['payment_type'];
                                        $recalculation_data[$arrange_counter]['remarks'] = $payment['remarks'];
                                        $recalculation_data[$arrange_counter]['pr_number'] = $payment['pr_number'];
                                        $recalculation_data[$arrange_counter]['or_number'] = $payment['or_number'];
                                        $recalculation_data[$arrange_counter]['bank'] = $payment['bank'];
                                        $recalculation_data[$arrange_counter]['bank_account_number'] = $payment['bank_account_number'];
                                        $recalculation_data[$arrange_counter]['check_number'] = $payment['check_number'];
                                        $recalculation_data[$arrange_counter]['payment_gateway_reference_number'] = $payment['payment_gateway_reference_number'];
                                        $recalculation_data[$arrange_counter]['payment_encode_type'] = $payment['payment_encode_type'];
                                        $recalculation_data[$arrange_counter]['payment_date'] = date('Y-m-d', strtotime($payment['paid_at']));
                                        $recalculation_data[$arrange_counter]['record_type'] = $payment['record_type'];
                                        $recalculation_data[$arrange_counter]['advance_payment'] = 0;
                                        $recalculation_data[$arrange_counter]['paid_at'] = Carbon::parse($payment['paid_at']);
                                        $recalculation_data[$arrange_counter]['user'] = $user_info;
        
                                        $arrange_counter++;
                                    }
                                    
                                }
                            }

                        }
                    }

                }
            }

            // RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
            // dd($recalculate, $recalculation_data);

        }

        $payment_amount = (float) $request->payment_amount;
        $remaining_balance = $payment_amount;
        $total_balance_in_house = $reservation->total_balance_in_house;  
        $interest_rate = $reservation->interest_rate / 100;
        $begining_balance = $total_balance_in_house;
        $interest = $total_balance_in_house * $interest_rate / 12;

        $paid_amortizations = [];
        $ids_to_retain = [];
        $is_balance_less_than_amount = false;
        $new_balance = 0;
        $currentBalance = 0;
        $prev_balance = 0;
        $penalty_only = false;
        $penalty_sched_created = false;
        $payment_amount_added = false;
        $sched_found = false;
        $balance_with_penalty = 0;
        $downpayment_actual_paid = 0;
        $reservation_actual_paid = 0;

        foreach( $reservation->payment_details as $key => $payment ) {

            if( $payment->payment_type == 'reservation_fee_payment' ) {
                $reservation_actual_paid = $reservation_actual_paid + $payment->payment_amount;
            }

            if( $payment->payment_type == 'downpayment' ) {
                $downpayment_actual_paid = $downpayment_actual_paid + $payment->payment_amount;
            }
        }

        $contract_amount = $reservation->with_twelve_percent_vat ? $reservation->net_selling_price_with_vat : $reservation->net_selling_price;
        $currentBalance = ($contract_amount - $downpayment_actual_paid) - $reservation_actual_paid;

        if( $newPayment && ($request->payment_type === 'monthly_amortization_payment' || $request->payment_type === 'penalty') && $reservation->old_reservation === 0 && $payment_amount > 0 && $reservation->payment_terms_type == 'in_house' ) {

            if( $first_date_paid === false && $last_date_paid === false ) {
                $is_previous_date = false;
            } else {
                $rdaysWithTOne = [1, 3, 5, 7, 8, 10, 12];
                $raddDays = in_array($last_date_paid->month, $rdaysWithTOne) ? 31 : 30;
                if( $last_date_paid->month == 2 ) {
                    $raddDays = ($last_date_paid->isLeapYear() && $last_date_paid->day < 29) ? 29 : 28;
                }
                $rnexDueDate = Carbon::parse($last_date_paid)->startOfDay()->addDays($raddDays);

                $is_previous_date = Carbon::parse($request->paid_at)->startOfDay()->lt($rnexDueDate);        
            }

            // disabling of override feature
            $is_previous_date = false;

            // if( $is_previous_date && !is_null($paid_key) && !isset($request->payment_form) ) {
            if( $is_previous_date && !is_null($paid_key) ) {
                AmortizationPenalty::where('reservation_number', $reservation->reservation_number)->where('discount', 100)->delete();
                $prev_penalties = [];
                if( isset($request->recompute) ) {
                    if( $request->recompute == true ) {
                        $prev_penalty_records = AmortizationPenalty::where('reservation_number', $reservation->reservation_number)->get();
                        foreach ($prev_penalty_records as $prev_penalty_record) {
                            if( isset($prev_penalties[$prev_penalty_record['number']]) ) {
                                $prev_penalties[$prev_penalty_record['number']] = $prev_penalties[$prev_penalty_record['number']] + $prev_penalty_record['amount_paid'];
                            } else {
                                $prev_penalties[$prev_penalty_record['number']] = $prev_penalty_record['amount_paid'];
                            }
                            
                        }
                    }
                }
                AmortizationPenalty::where('reservation_number', $reservation->reservation_number)->update([
                    'paid_at' => null,
                    'amount_paid' => null,
                ]);
                $recalculate = true;
            }

            if( $recalculate && !isset($request->recalculate_request) && $disabled ) {

                $reservation_query_for_recalculate = Reservation::where('reservation_number', $request->reservation_number)
                            ->with('client.information')
                            ->with(['amortization_schedule' => function($q){
                                $q->orderBy('number', 'ASC')->orderBy('id', 'ASC');
                            }, 'amortization_schedule.penalties', 'amortization_schedule.payments'])
                            ->first();

                $amortization_schedule_record = $reservation_query_for_recalculate->amortization_schedule;
                $data_counter = 0;
                $generated_id_lists = [];
                $calc_number = 0;
                $calc_payment_gateway = '';
                $calc_payment_type = '';
                $request_month = Carbon::parse($request->paid_at)->month;
                // Creation of data for recalculation
                $request_transaction_id = $this->generate_transaction_id([]);
                $generated_id_lists[] = $request_transaction_id;
                $included = false;
                foreach( $amortization_schedule_record as $key => $record ) {

                    if( $record['is_collection'] ) {

                        $sched_recalc_payment = RealEstatePayment::where('transaction_id', $record['transaction_id'])->first();
                        $details_added = false;
                        $recalculate_dueDate = Carbon::parse($record['due_date'])->startOfDay();
                        $recalculate_daysWithTOne = [1, 3, 5, 7, 8, 10, 12];
                        $recalculate_addDays = in_array($recalculate_dueDate->month, $recalculate_daysWithTOne) ? 31 : 30;
                        if( $recalculate_dueDate->month == 2 ) {
                            $recalculate_addDays = ($recalculate_dueDate->isLeapYear() && $recalculate_dueDate->day < 29) ? 29 : 28;
                        }
                        $recalculate_nexDueDate = Carbon::parse($record['due_date'])->startOfDay()->addDays($recalculate_addDays);
                        
                        $recalculate_paid_at_gte_start_date = Carbon::parse($request->paid_at)->startOfDay()->gte($recalculate_dueDate);
                        $recalculate_paid_at_lte_next_date = Carbon::parse($request->paid_at)->startOfDay()->lt($recalculate_nexDueDate);

                        if( $recalculate_paid_at_gte_start_date && $recalculate_paid_at_lte_next_date ) {
                            $recalculation_data[$record['number']] = [ 
                                'transaction_id' => $this->generate_transaction_id($generated_id_lists), 
                                'reservation_number' => $reservation->reservation_number,
                                'paid_at' => $request->paid_at, 
                                'payment_amount' => $request->payment_amount, 
                                'payment_gateway' => $request->payment_gateway,
                                'payment_type' => $request->payment_type,
                                'remarks' => $request->remarks,
                                'pr_number' => $request->pr_number,
                                'or_number' => $request->or_number,
                                'bank_account_number' => $request->account_number,
                                'user' => $user_info,
                            ];
                            $details_added = true;
                        }

                        $record_month = Carbon::parse($record['due_date'])->month;
                        $sched_transaction_id = $this->generate_transaction_id($generated_id_lists);
                        $generated_id_lists[] = $sched_transaction_id;
                        $data_counter++;

                        if( $sched_recalc_payment ) {
                            $calc_payment_gateway = $sched_recalc_payment->payment_gateway;
                            $calc_payment_type = $sched_recalc_payment->payment_type;
                        } else {
                            $calc_payment_gateway = $calc_payment_gateway;
                            $calc_payment_type = $calc_payment_type;
                        }

                        if( $record_month === $request_month ) {
                            $new_payment_amount = $request->payment_amount;

                            if( isset($request->recompute) ) {
                                if( $request->payment_type !== 'penalty' && isset($prev_penalties[$record['number']]) && $request->recompute == true ) {
                                    $new_payment_amount = $new_payment_amount + $prev_penalties[$record['number']];
                                    $details_added = false;
                                    $record['date_paid'] = $request->paid_at;
                                    $calc_payment_type = 'monthly_amortization_payment';
                                }
                            }

                        } else {
                            $new_payment_amount = $record->amount_paid;

                            if( $calc_number == $record['number'] ) {
                                $new_payment_amount = $record->amount_paid + $recalculation_data[$record['number']]['payment_amount'];
                            }
                        }

                        if( $details_added === false && $record['date_paid'] !== null ) {
                            $recalculation_data[$record['number']] = [ 
                                'transaction_id' => $sched_transaction_id, 
                                'reservation_number' => $reservation->reservation_number,
                                'paid_at' => Carbon::parse($record->date_paid), 
                                'payment_amount' => $new_payment_amount, 
                                'payment_gateway' => $calc_payment_gateway,
                                'payment_type' => $calc_payment_type,
                                'remarks' => $record['remarks'],
                                'pr_number' => $record['pr_number'],
                                'or_number' => $record['or_number'],
                                'bank_account_number' => $record['account_number'],
                                'user' => $user_info,
                            ];
                        }

                        if( $sched_recalc_payment && $details_added === false ) {
                            $recalculation_data[$record['number']]['payment_gateway_reference_number'] = $sched_recalc_payment->payment_gateway_reference_number;
                            $recalculation_data[$record['number']]['bank'] = $sched_recalc_payment->bank;
                            $recalculation_data[$record['number']]['check_number'] = $sched_recalc_payment->check_number;
                            $recalculation_data[$record['number']]['bank_account_number'] = $sched_recalc_payment->bank_account_number;
                        }

                        AmortizationSchedule::where('id', $record['id'])->update([
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

                        RealEstatePayment::where('transaction_id', $record['transaction_id'])->delete();
                        RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();

                        if( $calc_number == $record['number'] ) {
                            AmortizationSchedule::where('id', $record['id'])->delete();
                        }
                        $calc_number = $record['number'];
                        
                    }
                    
                }

            } else {
                $amortization_schedule_record = $reservation->amortization_schedule;
            }

            if( $recalculate && !isset($request->recalculate_request) ) {

                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                RealEstatePayment::whereIn('transaction_id', $tansactions_lists)->delete();
                AmortizationPenalty::where('reservation_number', $reservation->reservation_number)->delete();
                // dd($recalculation_data);
                AmortizationSchedule::where('reservation_number', $reservation->reservation_number)->update([
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
                AmortizationSchedule::where('reservation_number', $reservation->reservation_number)->where('is_sales', 0)->delete();

                foreach($recalculation_data as $key => $data) {

                    $addRequest = new AddPaymentRequest;
                    $addRequest->bulk_upload_request = true;
                    $addRequest->recalculate_request = true;
                    $addRequest->transaction_id = $data['transaction_id'];
                    $addRequest->reservation_number = $data['reservation_number'];
                    $addRequest->paid_at = $data['paid_at'];
                    $addRequest->payment_amount = $data['payment_amount'];
                    $addRequest->payment_gateway = $data['payment_gateway'];
                    $addRequest->payment_type = $data['payment_type'];
                    $addRequest->remarks = $data['remarks'];
                    $addRequest->pr_number = $data['pr_number'];
                    $addRequest->or_number = $data['or_number'];
                    $addRequest->bank_account_number = $data['bank_account_number'];
                    if( isset($request->record_type) ) {
                        $addRequest->record_type =  ($request->record_type !== false) ? $request->record_type : null;
                    }
                    $addRequest->record_type = isset($data['record_type']) ? $data['record_type'] : null;
                    $addRequest->advance_payment = isset($data['advance_payment']) ? $data['advance_payment'] : 0;
                    $addRequest->user = $data['user'];

                    if( isset($request->penalty_transaction_details) ) {
                        $addRequest->penalty_transaction_details = $request->penalty_transaction_details;
                    }

                    if( isset($request->amortization_transaction_details) ) {
                        $addRequest->amortization_transaction_details = $request->amortization_transaction_details;
                    }

                    $addPayment = new AddPayment;
                    $addPayment->__invoke($addRequest);
                }
            }

            foreach( $amortization_schedule_record as $key => $record ) {

                if($recalculate && !isset($request->recalculate_request)) {
                    break;
                }

                $amortization_id = $record['id'];
                $init_amortization_id = $record['id'];

                if( !$record['is_old'] || $record['is_old'] ) {

                    $is_penalty_type = false;
                    foreach($record->payments as $key => $payment) {
                        if( $payment->payment_type === 'penalty' ) {
                            $is_penalty_type = true;
                            break;
                        }
                    }

                    $is_present = Carbon::parse($request->paid_at)->startOfDay()->lte($record['due_date']);

                    if( $record['date_paid'] !== null ) {
                        $currentBalance = $record['balance'];
                    }

                    // if( $record['amount_paid'] !== null && $record['amount_paid'] < $record['amount'] && $is_present && !$is_penalty_type ) {
                    //     $record['date_paid'] = null;
                    //     $record['paid_status'] = null;
                    //     $currentBalance = $prev_balance;
                    //     $payment_amount = $payment_amount + $record['amount_paid'];
                    //     $payment_amount_added = true;
                    // }

                    if( $record['is_collection'] ) {
                        $ids_to_retain[] = $amortization_id;
                        $prev_balance = $record['balance'];
                    }

                    $dueDate = Carbon::parse($record['due_date'])->startOfDay();
                    $daysWithTOne = [1, 3, 5, 7, 8, 10, 12];
                    $addDays = in_array($dueDate->month, $daysWithTOne) ? 31 : 30;
                    if( $dueDate->month == 2 ) {
                        $addDays = ($dueDate->isLeapYear() && $dueDate->day < 29) ? 29 : 28;
                    }
                    $nexDueDate = Carbon::parse($record['due_date'])->startOfDay()->addDays($addDays);
                    
                    $paid_at_gte_start_date = Carbon::parse($request->paid_at)->startOfDay()->gte($dueDate);
                    $paid_at_lte_next_date = Carbon::parse($request->paid_at)->startOfDay()->lt($nexDueDate);

                    if( isset($request->payment_form) || isset($request->recompute) ) {
                        $date_check = true;
                    } else {
                        $date_check = ($paid_at_gte_start_date && $paid_at_lte_next_date) ? true : false;
                    }

                    if( $record['is_collection'] && $record['date_paid'] === null ) {

                        $currentBalance = AmortizationSchedule::getCurrentBalance(
                            $record, 
                            $currentBalance, 
                            $total_balance_in_house, 
                            false, 
                            $reservation->default_penalty_discount_percentage, 
                            $balance_with_penalty
                        );

                        $currentPenaltyId = $currentBalance['penalty_id'];
                        $currentPenalty = $currentBalance['penalty'];
                        $currentBalanceWithPenalty = $currentBalance['balance_with_penalty'];
                        $balance_with_penalty = $currentBalanceWithPenalty;
                        $currentPenaltyDiscount = $currentBalance['penalty_discount'];
                        $currentPenaltyStatus = $currentBalance['penalty_status'];
                        $currentBalance = $currentBalance['value'];
                        $record_count = $key + 1;
                        $sched_found = true;

                        if( is_null($record['date_paid']) && !in_array($currentPenaltyStatus, ['void', 'waived_wp', 'paid']) && $currentPenalty > 0 ) {
                            $balance_with_penalty = $currentBalanceWithPenalty;
                        } else {
                            $balance_with_penalty = 0;
                        }

                        $grace = Carbon::parse($record['due_date'])->startOfDay()->addDays(6);
                        $with_in_grace = Carbon::parse($request->paid_at)->startOfDay()->lt($grace);

                        $recalculate_interest = $total_balance_in_house * $interest_rate / 12;
                        if( $currentBalance != 0.00 || $currentBalance != 0 ) {
                            $recalculate_interest = $currentBalance * $interest_rate / 12;
                        }
                        $recalculate_principal = round($payment_amount - $recalculate_interest, 2);
                        $recalculate_principal = 1;
                        
                        if( $with_in_grace && $recalculate_principal > 0 ) {
                            $amortization_penalty = AmortizationPenalty::where('reservation_number', $reservation->reservation_number)->where('number', $record['number'])->get();
                            $amortization_penalty_count = $amortization_penalty->count();
                            
                            if( $amortization_penalty_count > 0 ) {
                                
                                RealEstatePayment::where('amortization_schedule_id', $record['id'])
                                    ->where('reservation_number', $reservation->reservation_number)
                                    ->delete();

                                AmortizationPenalty::where('reservation_number', $reservation->reservation_number)
                                    ->whereNull('paid_at')->whereNull('status')->whereNull('amount_paid')->delete();

                            }
                        }

                        // Handling penalty payment
                        $penalties = AmortizationPenalty::where('reservation_number', $reservation->reservation_number)
                            ->where('number', $record['number'])
                            ->where('discount', '!=', 100)
                            ->where(function($q){
                                $q->where(function($q2){
                                    $q2->whereRaw('amortization_penalties.penalty_amount != amortization_penalties.amount_paid');
                                })->orWhere(function($q3){
                                    $q3->whereNull('status')->whereNull('paid_at');
                                });
                            })->get();

                        $penalty_count = $penalties->count();

                        if( ($penalty_count > 0) && ($request->payment_type == 'penalty' || $request->payment_type == 'monthly_amortization_payment') ) {

                            $total_penalty = collect($penalties)->sum('penalty_amount');
                            $penalty_counter = 0;
                            
                            /**
                             * Rules on penalty
                             * 1. Payment amount should always equal to penalty amount.
                             *    - higher payment amount will adjust based on the penalty amount on the record.
                             * 2. Smaller payment amount will treat as a discounted amount and compute discount 
                             *    percentage based on the penalty amount.
                             * */ 

                            foreach($penalties as $key => $penalty) {

                                $d = (float) $penalty->discount;
                                $a = (float) $penalty->penalty_amount;
                                $rd = is_null($penalty->paid_at) ? $reservation->default_penalty_discount_percentage : $d;
                                $d = $penalty->discount > 0 ? $d : $rd;

                                $penalty_amount_with_discount = round(!is_nan( ($a - ($a * ($d / 100))) ) ? ($a - ($a * ($d / 100))) : 0, 2);

                                $update_payment_amount = false;
                                $is_payment_exist = true;
                                $penalty_id = $penalty->id;
                                $penalty_amount = (float) $penalty->penalty_amount;
                                $penalty_amount_paid = !is_null($penalty->amount_paid) ? (float) $penalty->amount_paid : 0;
                                $penalty_counter++;

                                if( $d > 0 ) {
                                    $penalty_amount  = $penalty_amount_with_discount;
                                }

                                if( $penalty_amount != $penalty_amount_paid ) {

                                    $payment_amount = $payment_amount + $penalty_amount_paid;

                                    if( $record->payments->count() > 0 ) {
                                        foreach($record->payments as $record_payment) {
                                            if( $record_payment['payment_type'] == 'penalty' ) {
                                                RealEstatePayment::where('transaction_id', $record_payment['transaction_id'])->delete();
                                            }
                                        }
                                    }
                                    
                                }

                                $penalty_payment = $payment_amount >= $penalty_amount ? $penalty_amount : $payment_amount;

                                // $currentSched = AmortizationSchedule::where('id', $amortization_id);
                                // $currentSched->update([
                                //     'date_paid' => $request->paid_at,
                                //     'paid_status' => 'completed',
                                //     'amount_paid' => $penalty_payment,
                                //     'pr_number' => $request->pr_number,
                                //     'or_number' => $request->or_number,
                                //     'account_number' => $request->bank_account_number,
                                //     'principal' => 0,
                                //     'interest' => 0,
                                //     'balance' => $currentBalance,
                                //     'remarks' => $request->remarks,
                                //     'type' => 'penalty',
                                // ]);

                                // $currentSched->whereNull('transaction_id')->update([
                                //     'transaction_id' => $generateTransactionID,
                                // ]);

                                // Amortization penalty
                                $penalty_update_data = [
                                    'amortization_schedule_id' => $amortization_id,
                                    'amount_paid' => $penalty_payment,
                                    'paid_at' => Carbon::now(),
                                    'discount' => $d,
                                ];

                                if( isset($request->with_penalty_details) ) {
                                    $penalty_update_data['remarks'] = $request->penalty_remarks;
                                }
                                AmortizationPenalty::where('id', $penalty->id)->update($penalty_update_data);

                                // Payments
                                $payment_data = [
                                    'amortization_schedule_id' => $amortization_id,
                                    'payment_amount' => $penalty_payment,
                                    'payment_type' => 'penalty',
                                    'paid_at' => $request->paid_at,
                                ];

                                if( isset($request->penalty_transaction_details) ) {
                                    $penalty_transaction_details = $request->penalty_transaction_details;
                                    if( !is_null($penalty_transaction_details['paid_at']) ) {
                                        $payment_data['payment_gateway'] = $penalty_transaction_details['payment_gateway'];
                                        $payment_data['remarks'] = $penalty_transaction_details['remarks'];
                                        $payment_data['cr_number'] = $penalty_transaction_details['pr_number'];
                                        $payment_data['or_number'] = $penalty_transaction_details['or_number'];
                                        $payment_data['bank'] = $penalty_transaction_details['bank'];
                                        $payment_data['bank_account_number'] = $penalty_transaction_details['bank_account_number'];
                                        $payment_data['check_number'] = $penalty_transaction_details['check_number'];
                                        $payment_data['payment_gateway_reference_number'] = $penalty_transaction_details['payment_gateway_reference_number'];
                                        $payment_data['payment_encode_type'] = $penalty_transaction_details['payment_encode_type'];
                                        $payment_data['record_type'] = $penalty_transaction_details['record_type'];
                                        $payment_data['paid_at'] = $penalty_transaction_details['paid_at'];
                                    }
                                }

                                RealEstatePayment::where('transaction_id', $generateTransactionID)->update($payment_data);

                                $payment_amount = $payment_amount - $penalty_payment;

                                // Amortization sched
                                // $new_sched = $this->create_schedule([
                                //     'reservation_number' => $reservation->reservation_number,
                                //     'record' => $record,
                                // ]);
                                // $amortization_id = $new_sched->id;

                                $penalty_only = ($payment_amount <= $total_penalty) ? true : false;

                                $total_penalty = $penalty_payment - $total_penalty;

                                if( $payment_amount <= 0 || $payment_amount <= 0.0 ) {
                                    
                                    AmortizationPenalty::where('id', '!=', $penalty->id)
                                        ->where('number', $record['number'])
                                        ->update(['amortization_schedule_id' => $amortization_id]);

                                    break;

                                } else {
                                    if( $payment_amount > $total_penalty && $request->payment_type != 'penalty' ) {

                                        $txn_amount = ( $payment_amount >= (float) $record['amount'] ) ? $record['amount'] : $payment_amount;

                                        $generateTransactionID = Str::upper(Str::random(10));
                                        while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
                                            $generateTransactionID = Str::upper(Str::random(10));
                                        }
                                        $request->new_payment_amount = $txn_amount;
                                        $this->new_payment_transaction($generateTransactionID, $reservation, $request, $sales_agent_name, $sales_manager_name, $user_info);
                                        unset($request->new_payment_amount);
                                    }
                                }
                                
                            }

                        }

                        // Stop looping of amortization for computation
                        if( $request->payment_type == 'penalty') {
                            if( $penalty_count <= 0 ) {
                                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                            }
                            break;
                        }

                        $total_penalty_paid = $this->check_penalty_amount($record['number'], $request);
                        if( $total_penalty_paid <= 0 ) {
                            
                            $penalty_only = false;

                            if( $penalty_count <= 0 && !is_null($record['amount_paid']) ) {
                                $payment_amount_paid = ($payment_amount_added) ? 0 : $record['amount_paid'];
                                $payment_amount = $payment_amount + $payment_amount_paid;
                            }

                        }

                        if( isset($request->re_dashboard_request) ) {
                            RealEstatePayment::where('id', $request->payment_id)->update([
                                'amortization_schedule_id' => $amortization_id,
                                'reservation_number' => $record['reservation_number'],
                                'advance_payment' => $request->advance_payment
                            ]);
                        }

                        if( $payment_amount <= 0 || $payment_amount <= 0.0 ) {
                            break;
                        }

                        if( ($record['amount_paid'] !== null && $record['amount_paid'] < $record['amount'] && $is_present && !$is_penalty_type) || ($penalty_count > 0) ) {
                            RealEstatePayment::where('transaction_id', $generateTransactionID)->update([
                                'amortization_schedule_id' => $amortization_id
                            ]);
                        }
                        
                        if( $currentBalance != 0.00 || $currentBalance != 0 ) {
                            $prev_balance = $currentBalance;
                            $interest = $currentBalance * $interest_rate / 12;
                        }

                        if( $request->advance_payment == '1' ) {
                            
                            $remaining_balance = ( $payment_amount >= $record['amount'] ) ? $payment_amount - $record['amount'] : 0;

                            //If the due date is not past, add the amount paid to payment and recalculate the amortization schedule balance
                            if( $record['amount_paid'] !== null && $record['amount_paid'] < $record['amount'] && $is_present ) {
                                $payment_amount_paid = ($payment_amount_added) ? 0 : $record['amount_paid'];
                                $payment_amount = $payment_amount + $payment_amount_paid;
                                $remaining_balance = ( $payment_amount >= $record['amount'] ) ? $payment_amount - $record['amount'] : 0;
                            }

                            if( $payment_amount > $record['amount'] && $payment_amount < $currentBalance ) {
                                $payment_amount = $record['amount'];
                            }

                        } else {
                            $remaining_balance = ( $payment_amount > $record['amount'] ) ? (($payment_amount * 100) / 100) - (($record['amount'] * 100) / 100) : 0;

                            if( $record['amount_paid'] !== null && $record['amount_paid'] < $record['amount'] && $is_present && !$penalty_only ) {
                                if( $payment_amount >= $record['amount'] ) {
                                    $payment_amount_paid = ($payment_amount_added) ? 0 : $record['amount_paid'];
                                    $remaining_balance = ($payment_amount + $payment_amount_paid) - $record['amount'];
                                } else {
                                    $remaining_balance = 0;
                                }
                            }
                            
                            if( $payment_amount > $record['amount'] && $payment_amount < $currentBalance ) {
                                $payment_amount = $record['amount'];
                            }
                        }

                        if( $record['amount_paid'] !== null && $payment_amount >= $record['amount_paid'] && $is_present && $request->advance_payment == '0' && !$is_penalty_type ) {
                            $payment_amount_paid = ($payment_amount_added) ? 0 : $record['amount_paid'];
                            $payment_amount = $payment_amount + $payment_amount_paid;
                        }

                        $principal = round($payment_amount - $interest, 2);
                        $balance = $currentBalance - $principal;
                        $balance = ($balance <= 0) ? 0 :  $balance;

                        if( $balance <= $record['amount'] ) {
                            $is_balance_less_than_amount = true;
                            $new_balance = $balance;
                        }

                        if( $request->advance_payment == '1' ) {
                            $currentBalance = $balance;
                        }
                        // handling of negative principal
                        if( $interest > $payment_amount && $principal < 0 ) {
                            $interest = ( !$penalty_only ) ? $payment_amount : 0;
                            $principal = 0;
                            $balance = $prev_balance;
                        }
        
                        if( $record['paid_status'] === null && $payment_amount > 0 && $request->payment_type != 'penalty' ) {

                            if( !is_null($record['transaction_id']) ) {
                                RealEstatePayment::where('transaction_id', $generateTransactionID)->update(['payment_amount' => $payment_amount]);
                                RealEstatePayment::where('transaction_id', $record['transaction_id'])->delete();
                            }

                            $remarks = $request->remarks;
                            $pr_number = $request->pr_number;
                            $or_number = $request->or_number;
                            $bank_account_number = $request->bank_account_number;
                            if( isset($request->amortization_transaction_details) ) {
                                $remarks = $request->amortization_transaction_details['remarks'];
                                $pr_number = $request->amortization_transaction_details['pr_number'];
                                $or_number = $request->amortization_transaction_details['or_number'];
                                $bank_account_number = $request->amortization_transaction_details['bank_account_number'];
                            }

                            AmortizationSchedule::where('id', $amortization_id)->update([
                                'transaction_id' => $generateTransactionID,
                                'date_paid' => $request->paid_at,
                                'paid_status' => 'completed',
                                'amount_paid' => $payment_amount,
                                'pr_number' => $pr_number,
                                'or_number' => $or_number,
                                'account_number' => $bank_account_number,
                                'principal' => $principal,
                                'interest' => $interest,
                                'balance' => $balance,
                                'remarks' => $remarks,
                            ]);

                            RealEstatePayment::where('transaction_id', $generateTransactionID)->update([
                                'amortization_schedule_id' => $amortization_id
                            ]);

                            if( $record_count === $schedule_count && $balance > 0 ) {

                                $schedules_to_update = AmortizationSchedule::whereNotIn('id', $ids_to_retain)
                                    ->where('reservation_number', $reservation->reservation_number);

                                $schedules_to_update->update([
                                    'is_collection' => 0
                                ]);
                            
                                $amortization_date = Carbon::parse($record['due_date'])->setTimezone('Asia/Manila');
                                $amortization_date->addMonth();

                                $balance = ($balance >= $record['amount']) ? $record['amount'] : $balance;
        
                                AmortizationSchedule::where('id', $amortization_id)
                                    ->create([
                                        'reservation_number' => $reservation->reservation_number,
                                        'number' => $record_count + 1,
                                        'due_date' => $amortization_date,
                                        'amount' => number_format($balance, 2, '.', ''),
                                        'date_paid' => null,
                                        'paid_status' => null,
                                        'amount_paid' => null,
                                        'pr_number' => null,
                                        'or_number' => null,
                                        'account_number' => null,
                                        'principal' => 0,
                                        'interest' => 0,
                                        'balance' => 0,
                                        'generated_principal' => 0,
                                        'generated_interest' => 0,
                                        'generated_balance' => 0,
                                        'remarks' => null,
                                        'is_old' => 0,
                                        'is_sales' => 0,
                                        'is_collection' => 1,
                                        'datetime' => null,
                                        'created_at' => Carbon::now()->setTimezone('Asia/Manila')
                                    ]);
                            }
        
                            $paid_amortizations[] = $record['reservation_number']."-".$record['number']." (".$payment_amount.")";

                            if( $request->advance_payment == '1' ) {
                                if( $remaining_balance <= 0 ) {
                                    // Stop the process last advance payment amount is lower than the amount due
                                    break;
                                } else {
                                    $payment_amount = $remaining_balance;

                                    $txn_amount = ( $payment_amount >= (float) $record['amount'] ) ? $record['amount'] : $payment_amount;
                                    RealEstatePayment::where('transaction_id', $generateTransactionID)->update(['payment_amount' => $txn_amount]);
                                        
                                    $generateTransactionID = Str::upper(Str::random(10));
                                    while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
                                        $generateTransactionID = Str::upper(Str::random(10));
                                    }
                                    $request->new_payment_amount = $txn_amount;
                                    $this->new_payment_transaction($generateTransactionID, $reservation, $request, $sales_agent_name, $sales_manager_name, $user_info);
                                    unset($request->new_payment_amount);
                                }
                            } else {

                                if( (isset($request->payment_form) || isset($request->recompute)) && $disabled ) {

                                    if( $is_present ) { 
                                        
                                        if( $remaining_balance > 0 && $balance > 0 ) {

                                            if( !is_null($record['transaction_id']) ) {
                                                RealEstatePayment::where('transaction_id', $generateTransactionID)->update(['payment_amount' => $payment_amount + $remaining_balance]);
                                                $is_exist = RealEstatePayment::where('transaction_id', $record['transaction_id'])->exists();
                                                $tid = ( $is_exist ) ? $record['transaction_id'] : $generateTransactionID;
                                            } else {
                                                RealEstatePayment::where('transaction_id', $generateTransactionID)->update(['payment_amount' => $payment_amount + $remaining_balance]);
                                                $tid = $generateTransactionID;
                                            }

                                            $this->create_schedule([
                                                'reservation_number' => $reservation->reservation_number,
                                                'transaction_id' => $tid,
                                                'number' => $record['number'],
                                                'due_date' => $record['due_date'],
                                                'amount' => $record['amount'],
                                                'date_paid' => $request->paid_at,
                                                'paid_status' => 'completed',
                                                'amount_paid' => $remaining_balance,
                                                'pr_number' => null,
                                                'or_number' => null,
                                                'account_number' => null,
                                                'principal' => $remaining_balance,
                                                'interest' => 0,
                                                'balance' => $balance - $remaining_balance,
                                                'remarks' => null,
                                                'excess_payment' => 1
                                            ]);

                                        }

                                        break;

                                    } else {

                                        if( $remaining_balance > 0 ) {
                                            $payment_amount = $remaining_balance;

                                            $txn_amount = ( $payment_amount >= (float) $record['amount'] ) ? $record['amount'] : $payment_amount;
                                            
                                            $generateTransactionID = Str::upper(Str::random(10));
                                            while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
                                                $generateTransactionID = Str::upper(Str::random(10));
                                            }
                                            $request->new_payment_amount = $txn_amount;
                                            $this->new_payment_transaction($generateTransactionID, $reservation, $request, $sales_agent_name, $sales_manager_name, $user_info);
                                            unset($request->new_payment_amount);

                                        } else {
                                            break;
                                        }
                                    }

                                } else {

                                    if( $remaining_balance > 0 && $balance > 0 && $remaining_balance != 0.00 && $balance != 0.00 ) {

                                        if( !is_null($record['transaction_id']) ) {
                                            RealEstatePayment::where('transaction_id', $generateTransactionID)->update(['payment_amount' => $payment_amount + $remaining_balance]);
                                            $is_exist = RealEstatePayment::where('transaction_id', $record['transaction_id'])->exists();
                                            $tid = ( $is_exist ) ? $record['transaction_id'] : $generateTransactionID;
                                        } else {
                                            RealEstatePayment::where('transaction_id', $generateTransactionID)->update(['payment_amount' => $payment_amount + $remaining_balance]);
                                            $tid = $generateTransactionID;
                                        }

                                        $this->create_schedule([
                                            'reservation_number' => $reservation->reservation_number,
                                            'transaction_id' => $tid,
                                            'number' => $record['number'],
                                            'due_date' => $record['due_date'],
                                            'amount' => $record['amount'],
                                            'date_paid' => $request->paid_at,
                                            'paid_status' => 'completed',
                                            'amount_paid' => $remaining_balance,
                                            'pr_number' => null,
                                            'or_number' => null,
                                            'account_number' => null,
                                            'principal' => $remaining_balance,
                                            'interest' => 0,
                                            'balance' => $balance - $remaining_balance,
                                            'remarks' => null,
                                            'excess_payment' => 1
                                        ]);

                                    }

                                    break;

                                }

                            }
                        }
                        
                    }

                } else {

                    // Applied to existing records which is an old implementation
                    if ((!$record['paid_status'] || $record['paid_status'] == 'partial') && $payment_amount > 0) {

                        $amortization_payment = $record['amount'];
                        $paid_status = 'completed';
                        $sched_found = true;
    
                        if ($record['amount'] > $payment_amount) {
                            $amortization_payment = $payment_amount;
                            $paid_status = 'partial';
                        }
    
                        if ($record['paid_status'] == 'partial') {
    
                            if ($payment_amount > ($record['amount'] - $record['amount_paid'])) {
                                $amortization_payment = $record['amount'] - $record['amount_paid'];
                                $paid_status = 'completed';
                            } else {
                                $amortization_payment = $payment_amount;
                            }
    
                            AmortizationSchedule::where('id', $amortization_id)
                                ->update([
                                    'transaction_id' => $generateTransactionID,
                                    'date_paid' => Carbon::now(),
                                    'paid_status' => $paid_status,
                                    'amount_paid' => $record['amount_paid'] + $amortization_payment,
    
                                    'pr_number' => $request->pr_number,
                                    'or_number' => $request->or_number,
                                    'account_number' => $request->bank_account_number,
                                    'remarks' => $request->remarks,
                                ]);
                        } else {
                            AmortizationSchedule::where('id', $amortization_id)
                                ->update([
                                    'transaction_id' => $generateTransactionID,
                                    'date_paid' => Carbon::now(),
                                    'paid_status' => $paid_status,
                                    'amount_paid' => $amortization_payment,
    
                                    'pr_number' => $request->pr_number,
                                    'or_number' => $request->or_number,
                                    'account_number' => $request->bank_account_number,
                                    'remarks' => $request->remarks,
                                ]);
                        }
    
                        $paid_amortizations[] = $record['reservation_number']."-".$record['number']." (".$amortization_payment.")";
    
                        $payment_amount = $payment_amount - $amortization_payment;
                    }

                }

            }

            if( $sched_found === false && !$recalculate && !isset($request->recalculate_request) ) {
                RealEstatePayment::where('transaction_id', $generateTransactionID)->delete();
                return response()->json(['message' => 'Payment not saved due to amortization schedule is already paid.'], 400);
            }

        }

        // Adjust amortization schedule based on the balance
        if( $is_balance_less_than_amount && $new_balance > 0 && $reservation->payment_terms_type == 'in_house' ) {

            $schedules_to_update = AmortizationSchedule::whereNotIn('id', $ids_to_retain)
                ->where('reservation_number', $reservation->reservation_number);

            $schedules = AmortizationSchedule::whereIn('id', $ids_to_retain)
                ->where('reservation_number', $reservation->reservation_number)
                ->where('excess_payment', 0)
                ->orderBy('number', 'DESC')->orderBy('id', 'DESC')->first();

            $number = $schedules['number'] + 1;

            $amortization_date = Carbon::parse($schedules['due_date'])->setTimezone('Asia/Manila');
            $amortization_date->addMonth();

            $schedules_to_update->update([
                'is_collection' => 0
            ]);

            $new_balance = ($new_balance >= $amount_due) ? $amount_due : $new_balance;

            AmortizationSchedule::create([
                    'reservation_number' => $reservation->reservation_number,
                    'number' => $number,
                    'due_date' => $amortization_date,
                    'amount' => number_format($new_balance, 2, '.', ''),
                    'date_paid' => null,
                    'paid_status' => null,
                    'amount_paid' => null,
                    'pr_number' => null,
                    'or_number' => null,
                    'account_number' => null,
                    'principal' => 0,
                    'interest' => 0,
                    'balance' => 0,
                    'generated_principal' => 0,
                    'generated_interest' => 0,
                    'generated_balance' => 0,
                    'remarks' => null,
                    'is_old' => 0,
                    'is_collection' => 1,
                    'is_sales' => 0,
                    'datetime' => null,
                    'created_at' => Carbon::now()->setTimezone('Asia/Manila')
                ]);
            
            
        }

        if( $is_balance_less_than_amount && $new_balance <= 0 && $reservation->payment_terms_type == 'in_house' ) {

            $schedules_to_update = AmortizationSchedule::whereNotIn('id', $ids_to_retain)
                ->where('reservation_number', $reservation->reservation_number);
            
            $schedules = AmortizationSchedule::whereIn('id', $ids_to_retain)
                ->where('reservation_number', $reservation->reservation_number)
                ->orderBy('id', 'desc')->first();
            
            $number = $schedules['number'] + 1;

            $amortization_date = Carbon::parse($schedules['due_date'])->setTimezone('Asia/Manila');
            $amortization_date->addMonth();

            $schedules_to_update->update([
                'is_collection' => 0
            ]);
        }

        $description = ( isset(RealestateActivityLog::$paymentType[$request->payment_type]) ) ? RealestateActivityLog::$paymentType[$request->payment_type] : $request->payment_type;

        RealestateActivityLog::create([
            'reservation_number' => $reservation->reservation_number,
            'action' => 'add_payment',
            'description' => $description . ' payment was made with the transaction number: ' .$generateTransactionID . ' ',
            'model' => 'App\Models\SalesAdminPortal\Reservation',
            'properties' => null,
            'created_by' => $user_info->id,
        ]);

        $reservation2 = Reservation::where('reservation_number', $request->reservation_number)->first();

        if( $reservation2['recalculated'] === 0 ) {
            $Payment = new Payment;
            $request->collection_recalculate = true;
            $request->user = $user_info;
            $Payment->recompute_account($request);
        }

        $getPaymentDetails = Payment::getPaymentDetails($reservation2);
        $reservation2['payment_details'] = $getPaymentDetails;

        $reservation2['request_user_id'] = $user_info->id;

        $amortization_schedule = AmortizationSchedule::collections($reservation2);
        $reservation2['amortization_collections'] = $amortization_schedule;

        $cash_ledger = CashTermLedger::collections($reservation2);
        $reservation2['cash_ledger_collections'] = $cash_ledger;

        $reservation2['advance_payment'] = $request->advance_payment;

        return $reservation2->load('client.information')
                        ->load('agent.team_member_of.team')
                        ->load('co_buyers.details')
                        ->load('promos')
                        ->load('attachments')
                        ->load('referrer')
                        ->load('referrer_property_details')
                        ->load(['amortization_schedule.penalties', 'amortization_schedule.payments'])
                        ->load(['cash_term_ledger.penalties', 'cash_term_ledger.payments'])
                        ->load('sales_manager')
                        ->load('sales_director');

        //return $request->all();
        
    }

    public function new_payment_transaction($generateTransactionID, $reservation, $request, $sales_agent_name, $sales_manager_name, $user_info)
    {

        $first_name = ($request->first_name) ? $request->first_name : $reservation->client->first_name;
        $last_name = ($request->last_name) ? $request->last_name : $reservation->client->last_name;
        $payment_gateway = $request->payment_gateway;
        $bank = $request->bank;
        $check_number = $request->check_number;
        $bank_account_number = $request->bank_account_number;
        $or_number = $request->or_number;
        $pr_number = ($request->cr_number != null) ? $request->cr_number : $request->pr_number;
        $payment_gateway_reference_number = $request->payment_gateway_reference_number;
        $remarks = $request->remarks;
        $payment_encode_type = 'admin';
        $paid_at = $request->paid_at;

        if( isset($request->amortization_transaction_details) ) {

            $amortization_transaction_details = $request->amortization_transaction_details;
            $first_name = !is_null($amortization_transaction_details['first_name']) ? $amortization_transaction_details['first_name'] : $first_name;
            $last_name = !is_null($amortization_transaction_details['last_name']) ? $amortization_transaction_details['last_name'] : $last_name;
            $payment_gateway = !is_null($amortization_transaction_details['payment_gateway']) ? $amortization_transaction_details['payment_gateway'] : $payment_gateway;
            $bank = $amortization_transaction_details['bank'];
            $check_number = $amortization_transaction_details['check_number'];
            $bank_account_number = $amortization_transaction_details['bank_account_number'];
            $or_number = $amortization_transaction_details['or_number'];
            $pr_number = $amortization_transaction_details['pr_number'];
            $payment_gateway_reference_number = $amortization_transaction_details['payment_gateway_reference_number'];
            $remarks = $amortization_transaction_details['remarks'];
            $payment_encode_type = $amortization_transaction_details['payment_encode_type'];
            $paid_at = !is_null($amortization_transaction_details['paid_at']) ? $amortization_transaction_details['paid_at'] : $paid_at;

        }

        $payment = RealEstatePayment::create([
            'transaction_id' => $generateTransactionID,
            // 'client_id' => $request->client_id,
            'reservation_number' => ($reservation) ? $reservation->reservation_number : '',
            'client_number' => ($reservation) ? $reservation->client_number : $request->client_number,
            'amortization_schedule_id' => isset($request->new_amortization_schedule_id) ? $request->new_amortization_schedule_id : null,
            'cash_term_ledger_id' => isset($request->cash_term_ledger_id) ? $request->cash_term_ledger_id : null,
            'first_name' => $first_name,
            'middle_name' => ($reservation) ? $reservation->client->middle_name : '',
            'last_name' => $last_name,
            'email' => ($reservation) ? $reservation->client->email : '',
            'contact_number' => $reservation->client->information->contact_number ?? '',
            'sales_agent' => ($sales_agent_name) ? $sales_agent_name : '',
            'sales_manager' => ($sales_manager_name) ? $sales_manager_name : '',
            'currency' => 'PHP',
            'payment_amount' => isset($request->new_payment_amount) ? $request->new_payment_amount : $request->payment_amount,
            'payment_gateway' => $payment_gateway,
            'payment_type' => $request->payment_type,
            // 'payment_channel' => $request->payment_channel,
            'payment_encode_type' => $payment_encode_type,
            'payment_gateway_reference_number' => $payment_gateway_reference_number,
            'remarks' => $remarks,
            'discount' => (!is_null($request->discount)) ? $request->discount : 0,

            'paid_at' => $request->paid_at,

            'bank' => $bank,
            'bank_account_number' => $bank_account_number,
            'cr_number' => $pr_number,
            'or_number' => $or_number,
            'is_verified' => 1,
            'verified_date' => Carbon::now(),
            'verified_by' => $user_info->id,
            'advance_payment' => ( isset($request->advance_payment) ) ? $request->advance_payment : 0,
            'check_number' => $check_number,
            'record_type' => (isset($request->record_type) && $request->record_type != false) ? $request->record_type : null,
        ]);

        if( !isset($request->re_dashboard_request) ) {
            $payment->paymentStatuses()->create([
                'status' => $request->paid_at ? 'SUCCESS_ADMIN' : 'PENDING_ADMIN',
                'message' => 'Paid via Admin.'
            ]);
        }

        return $payment;
    }

    public function create_schedule($params)
    {
        $balance = isset($params['balance']) ? $params['balance'] : 0;
        $balance = ( $balance < 0 ) ? 0 : $balance;

        return AmortizationSchedule::create([
            'reservation_number' => $params['reservation_number'],
            'transaction_id' => isset($params['transaction_id']) ? $params['transaction_id'] : null,
            'number' => isset($params['record']['number']) ? $params['record']['number'] : $params['number'],
            'due_date' => isset($params['record']['due_date']) ? $params['record']['due_date'] : $params['due_date'],
            'amount' => isset($params['record']['amount']) ? $params['record']['amount'] : $params['amount'],
            'date_paid' => isset($params['date_paid']) ? $params['date_paid'] : null,
            'paid_status' => isset($params['paid_status']) ? $params['paid_status'] : null,
            'amount_paid' => isset($params['amount_paid']) ? $params['amount_paid'] : null,
            'pr_number' => isset($params['record']['pr_number']) ? $params['record']['pr_number'] : (isset($params['pr_number']) ? $params['pr_number'] : null),
            'or_number' => isset($params['record']['or_number']) ? $params['record']['or_number'] : (isset($params['or_number']) ? $params['or_number'] : null),
            'account_number' => isset($params['record']['account_number']) ? $params['record']['account_number'] : (isset($params['account_number']) ? $params['account_number'] : null),
            'principal' => isset($params['principal']) ? $params['principal'] : 0,
            'interest' => isset($params['interest']) ? $params['interest'] : 0,
            'balance' => $balance,
            'generated_principal' => 0,
            'generated_interest' => 0,
            'generated_balance' => 0,
            'remarks' => isset($params['record']['remarks']) ? $params['record']['remarks'] : (isset($params['remarks']) ? $params['remarks'] : null),
            'is_old' => 0,
            'is_sales' => 0,
            'is_collection' => 1,
            'datetime' => null,
            'excess_payment' => isset($params['excess_payment']) ? $params['excess_payment'] : 0,
            'type' => isset($params['excess_penalty']) ? $params['excess_penalty'] : null,
            'created_at' => Carbon::now()->setTimezone('Asia/Manila')
        ]);
    }

    public function get_next_due_date($date)
    {
        $dueDate = Carbon::parse($date)->startOfDay();

        $daysWithTOne = [1, 3, 5, 7, 8, 10, 12];

        if( in_array($dueDate->month, $daysWithTOne) ) {
            if( $dueDate->day == 31 ) {
                $addDays = 30;
            } else {
                $addDays = 31;
            }
        } else {
            $addDays = 30;
        }

        // $addDays = in_array($dueDate->month, $daysWithTOne) ? 31 : 30;

        if( $dueDate->month == 1 ) {

            if( $dueDate->day == 31 ) {
                $addDays = ($dueDate->isLeapYear() && $dueDate->day < 29) ? 29 : 28;
            } else {
                $addDays = ($dueDate->isLeapYear() && $dueDate->day < 29) ? 30 : 29;
            }

            // $addDays = ($dueDate->isLeapYear() && $dueDate->day < 29) ? 30 : 29;
        }

        if( $dueDate->month == 2 ) {
            $addDays = ($dueDate->isLeapYear() && $dueDate->day < 29) ? 29 : 28;
        }
        
        return Carbon::parse($date)->startOfDay()->addDays($addDays);
    }

    public function check_penalty_amount($number, $request)
    {
        $penalties = AmortizationPenalty::where('reservation_number', $request->reservation_number)->where('number', $number)->whereNull('paid_at')->get();
        return collect($penalties)->sum('penalty_amount');
    }

    public function generate_transaction_id($generated_id = [])
    {
        $generateTransactionID = Str::upper(Str::random(10));
        while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists() || in_array($generateTransactionID, $generated_id)) {
            $generateTransactionID = Str::upper(Str::random(10));
        }
        return $generateTransactionID;
    }

    public function penaltyPayment(Request $request)
    {
        $id = $request->id;
        $discount = (float) $request->discount;
        $penalty_amount = (float) $request->penalty_amount;
        $remarks = $request->remarks;

        if( isset($request->bulk_upload_request) ) {
            $amount_paid = $request->amount_paid;
            $paid_at = $request->paid_at;
            $user = $request->user;
        } else {
            $amount_paid = round($penalty_amount - ( $penalty_amount * ( $discount / 100 ) ), 2);
            $paid_at = Carbon::now();
            $user = $request->user();
        }

        if( $request->payment_terms_type === 'cash' ) {

            $penalty_record = CashTermPenalty::where('id', $id)->first();
            $reservation_number = $penalty_record->reservation_number;

            if( is_null($penalty_record->amount_paid) ) {

                CashTermPenalty::where('id', $id)->update([
                    'penalty_amount' => $penalty_amount,
                    'discount' => $discount,
                    'amount_paid' => $amount_paid,
                    'remarks' => $remarks,
                    'paid_at' => $paid_at
                ]);
    
                $addRequest = new AddPaymentRequest;
                $addRequest->bulk_upload_request = true;
                $addRequest->reservation_number = $reservation_number;
                $addRequest->paid_at = $paid_at;
                $addRequest->payment_amount = $amount_paid;
                $addRequest->cash_term_ledger_id = $id;
                $addRequest->payment_gateway = 'Cash';
                $addRequest->payment_type = 'penalty';
                $addRequest->user = $user;
                $addPayment = new AddPayment;
                $addPayment->__invoke($addRequest);

            } else {
                $penalty_record->update([
                    'penalty_amount' => $penalty_amount,
                    'amount_paid' => $amount_paid,
                    'remarks' => $remarks,
                    'discount' => $discount,
                ]);

                if( $request->transaction_id != false ) {
                    RealEstatePayment::where('transaction_id', $request->transaction_id)->update(['payment_amount' => $amount_paid]);
                }
            }

        } else {
            $penaltyComputed = 0;
            $amortization_id = $request->amortization_id;
            $schedule = AmortizationSchedule::where('id', $amortization_id);
            $schedule_record = $schedule->first();
            $date_paid = $schedule_record->date_paid;
            $reservation_number = $schedule_record->reservation_number;
            $penalty = AmortizationPenalty::where('reservation_number', $reservation_number)->where('id', $id);
            $transaction_id = $request->transaction_id;

            if( is_null($schedule->first()->date_paid) && is_null($penalty->first()->status) ) {

                if( is_null($schedule->first()->date_paid) ) {
                    $penalty->update([
                        'penalty_amount' => $penalty_amount,
                        'discount' => $discount,
                        'remarks' => $remarks,
                    ]);
    
                    $addRequest = new AddPaymentRequest;
                    $addRequest->bulk_upload_request = true;
                    $addRequest->reservation_number = $reservation_number;
                    $addRequest->paid_at = Carbon::parse($schedule->first()->due_date)->endOfDay()->addDays(6);
                    $addRequest->payment_amount = $amount_paid;
                    $addRequest->payment_gateway = 'Cash';
                    $addRequest->payment_type = 'penalty';
                    $addRequest->user = $request->user();
                    $addPayment = new AddPayment;
                    $addPayment->__invoke($addRequest);

                    // $amortization_with_penalties = AmortizationSchedule::where('reservation_number', $reservation_number)
                    //     ->where('is_collection', 1)
                    //     ->whereNull('paid_at')
                    //     ->whereNull('type')
                    //     ->with('penalties')->get();

                    // if( $amortization_with_penalties ) {

                    //     foreach( $amortization_with_penalties as $key => $schedule ) {
                    //         if( $schedule->penalties->count() > 0 ) {
                    //             $penalty_record = $schedule->penalties->first();
                    //             if( is_null($penalty_record->paid_at) ) {

                    //                 $penalty_id = $penalty_record->id;
                    //                 $amount_due = $schedule['amount'];

                    //                 if( $penaltyComputed <= 0 ) {
                    //                     $computedPenalty = $amount_due * 0.03;
                    //                     $penaltyComputed = $computedPenalty + $amount_due;
                    //                 } else {
                    //                     $computedPenalty = $penaltyComputed * 0.03;
                    //                     $penaltyComputed = $computedPenalty + $penaltyComputed;
                    //                 }
                    //                 AmortizationPenalty::where('reservation_number', $reservation_number)
                    //                     ->where('id', $penalty_id)->update([
                    //                         'penalty_amount' => $computedPenalty
                    //                     ]);
                    //             }
                    //         }
                    //     }

                    // }

                } else {
                    $schedule->update(['amount_paid' => $amount_paid]);
                    RealEstatePayment::where('transaction_id', $schedule->first()->transaction_id)->update(['payment_amount' => $amount_paid]);
                    $penalty->update([
                        'penalty_amount' => $penalty_amount,
                        'discount' => $discount,
                        'amount_paid' => $amount_paid,
                        'remarks' => $remarks,
                    ]);
                }

            } else {
                $penalty->update([
                    'penalty_amount' => $penalty_amount,
                    'discount' => $discount,
                    'amount_paid' => $amount_paid,
                    'remarks' => $remarks,
                ]);

                if( $transaction_id != false ) {
                    RealEstatePayment::where('transaction_id', $transaction_id)->update(['payment_amount' => $amount_paid]);
                }
            }

        }
    }
}
