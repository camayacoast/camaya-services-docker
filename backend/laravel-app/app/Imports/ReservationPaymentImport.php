<?php

namespace App\Imports;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\AmortizationPenalty;
use App\Models\RealEstate\RealEstatePayment;
use App\Models\RealEstate\RealEstatePaymentStatus;
use App\Http\Controllers\SalesAdminPortal\AddPayment;
use App\Http\Requests\RealEstate\AddPaymentRequest;
use App\Models\RealEstate\RealestatePaymentActivityLog;
use App\Models\RealEstate\CashTermPenalty;
use App\Models\RealEstate\RealestateActivityLog;
use App\Models\RealEstate\AmortizationSchedule;
use App\Models\RealEstate\CashTermLedger;
use App\User;
use Illuminate\Support\Str;

use Carbon\Carbon;

class ReservationPaymentImport implements ToCollection
{

    public $reports;
    public $user;
    public $created_old_reservation_number = [];
    public $old_record_lists = [];
    public $recompute = false;
    public $record_type = false;
    public $waive_penalty = false;

    public function process_non_existing_client($rows)
    {
        foreach( $rows as $key => $row ) {

            if( $key !== 0 ) {

                try {
                    // Cell format set to "Date"
                    $row[1] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1])->format('m/d/Y');
                } catch(\ErrorException $e) {
                    // Cell format set to "Text"
                    $row[1] = $row[1];
                }
                
                $paid_at = Carbon::parse($row[1])->setTimezone('Asia/Manila');
                $payment_amount = $row[2];
                $client_number = $row[3];
                $first_name = $row[4];
                $last_name = $row[5];
                $payment_type = $row[6];

                if( $payment_amount !== null && $client_number !== null && $first_name !== null && $last_name !== null && $payment_type !== null  ) {

                    $reservation = Reservation::where('client_number', $client_number)->with('payment_details')->first();

                    if( !$reservation ) {

                        if( !isset($this->old_record_lists[$client_number]) ) {

                            $this->created_old_reservation_number[$client_number] = "R-".\Str::upper(\Str::random(12));
                            // Creates a new reference number if it encounters duplicate
                            while (Reservation::where('reservation_number', $this->created_old_reservation_number[$client_number])->exists()) {
                                $this->created_old_reservation_number[$client_number] = "R-".\Str::upper(\Str::random(12));
                            }

                            $client = User::where('first_name', $first_name)->where('last_name', $last_name)->where('user_type', 'client')->first();

                            if( !$client ) {
                                $newClient = User::create([
                                    'object_id' => (string) Str::orderedUuid(),
                                    'first_name' => $first_name,
                                    'middle_name' => '',
                                    'last_name' => $last_name,
                                    'user_type' => 'client',
                                    'email' => strtolower($first_name . '' . $last_name) . '@oldrecord.com',
                                    'email_verified_at' => null,
                                    'password' => Str::random(10)
                                ]);
                                $clientID = $newClient->id;
                            } else {
                                $clientID = $client->id;
                            }

                            // New reservation
                            $new_reservation = Reservation::create([
                                'client_id' => $clientID,
                                'agent_id' => 0,
                                'sales_manager_id' => null,
                                'sales_director_id' => null,
                                'referrer_id' => null,
                                'referrer_property' => null,
                                'status' => 'approved',
                                'client_number' => $client_number,
                                'reservation_number' => $this->created_old_reservation_number[$client_number],
                                'remarks' => null,
                                'source' => null,
                                'interest_rate' => 7, // assuming that all not existing uploads are old records
                                'property_type' => null,
                                'subdivision' => '',
                                'block' => '',
                                'lot' => '',
                                'type' => null,
                                'area' => null,
                                'price_per_sqm' => null,
                                'total_selling_price' => 0,
                                'reservation_fee_date' => $paid_at,
                                'reservation_fee_amount' => 0,
                                'payment_terms_type' => $payment_type === 'monthly_amortization_payment' ? 'in_house' : 'cash',
                                'discount_amount' => 0,
                                'with_twelve_percent_vat' => 0,
                                'with_five_percent_retention_fee' => 1,
                                'split_cash' => 0,
                                'number_of_cash_splits' => 0,
                                'split_cash_start_date' => null,
                                'split_cash_end_date' => null,
                                'downpayment_amount' => 0,
                                'downpayment_due_date' => $paid_at,
                                'number_of_years' => 1,
                                'factor_rate' => 0.0865267, 
                                'monthly_amortization_due_date' => $paid_at,
                                'split_downpayment' => 0,
                                'number_of_downpayment_splits' => 1,
                                'split_downpayment_start_date' => null,
                                'split_downpayment_end_date' => null,
                                'old_reservation' => 1,
                            ]);

                            if( $payment_type === 'monthly_amortization_payment' ) {
                                Reservation::where('reservation_number', $this->created_old_reservation_number[$client_number])->update([
                                    'payment_terms_type' => 'in_house',
                                    'with_five_percent_retention_fee' => 0,
                                ]);
                            }

                            $this->old_record_lists[$client_number] = $client_number;

                        }

                    } else {

                        if( $payment_type === 'monthly_amortization_payment' && isset($this->old_record_lists[$client_number]) ) {
                            Reservation::where('reservation_number', $this->created_old_reservation_number[$client_number])->update([
                                'payment_terms_type' => 'in_house',
                                'with_five_percent_retention_fee' => 0,
                            ]);
                        }

                    }

                }
                
            }

        }
    }

    public function collection(Collection $rows)
    {
        $client_data = [];
        $reports = [];
        $row_payments = [];
        $penalty_rows = [];
        $amortization_rows = [];
        $penalty_check_count = 0;
        $amortization_check_count = 0;

        // Arrange payments by date

        $date_array = [];
        foreach( $rows as $key => $value ) {
            if($key !== 0) {
                $date_array[$key] = [ 'date' => date('Y-m-d', strtotime($value[1]))];
            }
            
        }

        $date_collection = collect($date_array);
        $sorted_rows = $date_collection->sortBy('date');
        $sorted_rows->all();


        $arrange_rows = [[]];
        foreach( $sorted_rows as $key => $value ) {
            if($key !== 0) {
                $arrange_rows[] = $rows[$key];
            } else {
                $arrange_rows = [];
            }
            
        }

        // dd($arrange_rows);

        // Arrange payments by priorities
        foreach( $arrange_rows as $rowIndex => $row ) {
            if( $rowIndex !== 0 ) {
                $pt = $row[6];

                if( $pt == 'penalty' ) {
                    $penalty_check_count++;
                    $penalty_rows[] = $row;
                }

                if( $pt == 'monthly_amortization_payment' ) {

                    if( $penalty_check_count > 0 ) {
                        $amortization_rows[] = array_shift($penalty_rows);
                        $penalty_check_count--;
                    }

                    $amortization_check_count++;
                    $amortization_rows[] = $row;
                }

                // if( $penalty_check_count > 0 && $amortization_check_count > 0 ) {
                    // $row_payments[] = array_shift($penalty_rows);
                    // $row_payments[] = array_shift($amortization_rows);
                    // $amortization_rows[] = array_shift($penalty_rows);
                    // $penalty_check_count--;
                    // $amortization_check_count--;
                // }

                if( !in_array($pt, ['monthly_amortization_payment', 'penalty'])  ) {
                    $row_payments[] = $row;
                }

            } else {
                $row_payments[] = $row;
            }
        }

        $final_payments = array_merge($row_payments, $amortization_rows);
        $final_payments = array_merge($final_payments, $penalty_rows);

        $final_payments = ( $this->recompute ) ? $rows : $final_payments;

        // dd($final_payments);

        // Creation of old reservation
        // $this->process_non_existing_client($rows);

        // Import Payments
        foreach( $final_payments as $key => $row ) {
            if( $key !== 0 ) {

                // Validate the cell format of the date field
                try {
                    // Cell format set to "Date"
                    $row[1] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1])->format('m/d/Y');
                } catch(\ErrorException $e) {
                    // Cell format set to "Text"
                    $row[1] = $row[1];
                }

                $paid_at = Carbon::parse($row[1])->setTimezone('Asia/Manila');
                $payment_amount = $row[2];
                $client_number = $row[3];
                $first_name = $row[4];
                $last_name = $row[5];
                $payment_type = $row[6];
                $payment_gateway = $row[7];

                $payment_types = array_map('trim', explode(',', $payment_type));
                $payment_types_count = count($payment_types);

                if( $payment_types_count > 1 ) {

                    $reservation = Reservation::where('client_number', $client_number)
                        ->with(['amortization_schedule' => function($q){
                            $q->whereNull('type')->OrWhereNotIn('type', ['penalty'])->orderBy('number', 'ASC')->orderBy('id', 'ASC');
                        }, 'amortization_schedule.penalties', 'amortization_schedule.payments'])
                        ->with('payment_details')
                        ->first();

                    if( $reservation ) {

                        $collection = new Collection;
                        $collection[] = [];
                        $index = 0;
                        $arranged_payment_type = [];
                        $paid_lists = [];
                        $one_time_payment_types = ['reservation_fee_payment', 'retention_fee', 'title_fee'];

                        foreach( $reservation->payment_details as $payment ) {
                            if( in_array($payment->payment_type, $one_time_payment_types) ) {

                                if( $payment->payment_type === 'reservation_fee_payment' ) {
                                    $reservation_amount = $reservation->reservation_fee_amount;
                                    $is_completed = ($payment->payment_amount >= $reservation_amount) ? true : false;
                                }

                                if( $payment->payment_type === 'retention_fee' ) {
                                    if( $reservation->with_five_percent_retention_fee ) {
                                        $retention_amount = $reservation->retention_fee;
                                        $is_completed = ($payment->payment_amount >= $retention_amount) ? true : false;
                                    } else {
                                        $is_completed = true;
                                    }
                                }

                                if( $payment->payment_type === 'title_fee' ) {
                                    $contract_amount = $reservation->with_twelve_percent_vat ? $reservation->net_selling_price_with_vat : $reservation->net_selling_price;
                                    $title_fee_amount = $contract_amount * 0.05;
                                    $is_completed = ($payment->payment_amount >= $title_fee_amount) ? true : false;
                                }

                                if( $is_completed ) {
                                    $paid_lists[] = $payment->payment_type;
                                }
                                
                            }
                        }

                        foreach( $payment_types as $ptype ) {
                            switch ($ptype) {
                                case 'reservation_fee_payment':
                                    if( !in_array($ptype, $paid_lists) ) {
                                        $arranged_payment_type[1] = $ptype;
                                    }
                                    break;
                                case 'retention_fee':
                                    if( $reservation->payment_terms_type == 'cash' ) {
                                        if( !in_array($ptype, $paid_lists) ) {
                                            $arranged_payment_type[2] = $ptype;
                                        }
                                    }
                                    break;
                                case 'downpayment':
                                case 'split_cash': 
                                case 'partial_cash': 
                                case 'full_cash':
                                    $arranged_payment_type[3] = $ptype;
                                    break;
                                
                                case 'title_fee':
                                    if( !in_array($ptype, $paid_lists) ) {
                                        $arranged_payment_type[4] = $ptype;
                                    }
                                    break;
                                case 'penalty':

                                    if( $reservation->payment_terms_type == 'in_house' ) {
                                        $arranged_payment_type[5] = $ptype;
                                    }

                                    break;
                                case 'monthly_amortization_payment':
                                    if( $reservation->payment_terms_type == 'in_house' ) {
                                        $arranged_payment_type[6] = $ptype;
                                    }
                                    break;
                                case 'docs_fee':
                                    if( !in_array($ptype, $paid_lists) ) {
                                        $arranged_payment_type[7] = $ptype;
                                    }
                                    break;
                                case 'redocs_fee':
                                    if( !in_array($ptype, $paid_lists) ) {
                                        $arranged_payment_type[8] = $ptype;
                                    }
                                    break;
                                default:
                                    break;
                            }
                        }

                        ksort($arranged_payment_type);

                        $updated_reservation = Reservation::where('client_number', $client_number)
                            ->with(['amortization_schedule' => function($q){
                                $q->whereNull('type')->OrWhereNotIn('type', ['penalty'])->orderBy('number', 'ASC')->orderBy('id', 'ASC');
                            }, 'amortization_schedule.penalties', 'amortization_schedule.payments'])
                            ->with('payment_details')
                            ->first();

                        foreach( $arranged_payment_type as $k => $ptype ) {

                            if( $payment_amount <= 0 ) {
                                break;
                            }

                            switch ($ptype) {
                                case 'reservation_fee_payment':
                                    $index = 1;
                                    $remaining_balance = 0;

                                    $reservation_payment = RealEstatePayment::where('client_number', $client_number)
                                        ->where('payment_type', $ptype)->get();
                                    $reservation_payment_count = $reservation_payment->count();

                                    $reservation_amount = $updated_reservation->reservation_fee_amount;

                                    if( $reservation_payment_count > 0 )  {

                                        if( $reservation_payment[0]->payment_amount < $reservation_amount ) {
                                            $remaining_balance = $reservation_amount - $reservation_payment[0]->payment_amount;
                                        }

                                        if( $remaining_balance > 0 ) {

                                            if( ($payment_amount < $reservation_amount) ) {
                                                $reservation_amount = ($payment_amount > 0 && $payment_amount < $reservation_amount ) ? $payment_amount : 0;
                                            }

                                            if( $reservation_amount > 0 ) {
                                                $collection[$index] = new Collection([
                                                    $row[0], trim($row[1]), $reservation_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                                ]);
                                            }
                                        }

                                    } else {

                                        if( ($payment_amount < $reservation_amount) ) {
                                            $reservation_amount = ($payment_amount > 0 && $payment_amount < $reservation_amount ) ? $payment_amount : 0;
                                        }
        
                                        if( $reservation_amount > 0 ) {
                                            $collection[$index] = new Collection([
                                                $row[0], trim($row[1]), $reservation_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                            ]);
                                        }

                                    }

                                    $payment_amount = $payment_amount - $reservation_amount;

                                    break;
                                case 'retention_fee':
                                    $index = 2;
                                    $remaining_balance = 0;

                                    $retention_payment = RealEstatePayment::where('client_number', $client_number)
                                        ->where('payment_type', $ptype)->get();
                                    $retention_payment_count = $retention_payment->count();

                                    $retention_fee_amount = ($updated_reservation->with_five_percent_retention_fee) ? round($updated_reservation->retention_fee, 2) : 0;

                                    if( $retention_payment_count > 0 ) {

                                        if( $retention_payment[0]->payment_amount < $retention_fee_amount ) {
                                            $remaining_balance = $retention_fee_amount - $retention_payment[0]->payment_amount;
                                        }

                                        if( $remaining_balance > 0 ) {
                                            
                                            if( ($payment_amount < $retention_fee_amount) ) {
                                                $retention_fee_amount = ($payment_amount > 0 && $payment_amount < $retention_fee_amount ) ? $payment_amount : 0;
                                            }
            
                                            if( $retention_fee_amount > 0 ) {
                                                $collection[$index] = new Collection([
                                                    $row[0], trim($row[1]), $retention_fee_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                                ]);
                                            }

                                        }

                                    } else {

                                        if( ($payment_amount < $retention_fee_amount) ) {
                                            $retention_fee_amount = ($payment_amount > 0 && $payment_amount < $retention_fee_amount ) ? $payment_amount : 0;
                                        }
        
                                        if( $retention_fee_amount > 0 ) {
                                            $collection[$index] = new Collection([
                                                $row[0], trim($row[1]), $retention_fee_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                            ]);
                                        }

                                    }
        
                                    $payment_amount = $payment_amount - $retention_fee_amount;

                                    break;
                                case 'downpayment':
                                case 'split_cash': 
                                case 'partial_cash': 
                                case 'full_cash':
                                    $index = 3;

                                    if( $updated_reservation->payment_terms_type == 'in_house' ) {

                                        $downpayment_amount_payable = ( $updated_reservation->split_downpayment  ) ? $updated_reservation->downpayment_amount_less_RF : $updated_reservation->downpayment_amount;
                                        $downpayment_paid_counter = 0;
                                        $downpayment_amount = ( $updated_reservation->split_downpayment  ) ? round($updated_reservation->split_downpayment_amount, 2) : round($updated_reservation->downpayment_amount, 2);
                                        $downpayment_amount_paid = 0;

                                        if( $updated_reservation->split_downpayment == 1 ) {
                                            $number_of_splits = $updated_reservation->number_of_downpayment_splits;
                                        } else {
                                            $number_of_splits = 1;
                                        }

                                        foreach( $updated_reservation->payment_details as $detail ) {
                                            if( $detail->payment_type === 'downpayment' ) {
                                                if( $detail->payment_amount >= $downpayment_amount ) {
                                                    $downpayment_paid_counter++;
                                                }
                                                $downpayment_amount_paid = $downpayment_amount_paid  + $detail->payment_amount;
                                            }
                                        }

                                        if( $downpayment_paid_counter == $number_of_splits ) {
                                            $downpayment_amount_paid = round($downpayment_amount_paid);
                                        }

                                        if( $downpayment_amount_paid <= 0 ) {
                                            $cash_balance = (round($downpayment_amount_payable, 2) / $number_of_splits) * $number_of_splits;
                                        } else {
                                            if( $downpayment_amount_paid < ((round($downpayment_amount_payable, 2) / $number_of_splits) * $number_of_splits) ) {
                                                $cash_balance = (round(($downpayment_amount_payable / $number_of_splits),2) * $number_of_splits) - $downpayment_amount_paid;
                                            } else {
                                                $cash_balance = 0;
                                            }
                                        }

                                        if( $payment_amount <= $cash_balance ) {
                                            $downpayment_amount = $payment_amount;
                                        } else {
                                            $downpayment_amount = $cash_balance;
                                        }

                                    } else {

                                        $cash_ledgers = CashTermLedger::where('reservation_number', $updated_reservation->reservation_number)->get();

                                        $cash_ledger_count = $cash_ledgers->count();
                                        $cash_amount_paid = 0;
                                        $cash_paid_counter = 0;

                                        foreach( $cash_ledgers as $cash_ledger ) {
                                            if( !is_null($cash_ledger->paid_status) ) {
                                                $cash_paid_counter++;
                                            }
                                            $cash_amount_paid = $cash_amount_paid + $cash_ledger->amount_paid;
                                        }

                                        if( $cash_paid_counter == $cash_ledger_count ) {
                                            $cash_amount_paid = round($cash_amount_paid);
                                        }

                                        if( $cash_amount_paid <= 0 ) {
                                            $cash_balance = (round(($updated_reservation->total_amount_payable / $cash_ledger_count),2) * $cash_ledger_count);
                                        } else {
                                            if( $cash_amount_paid < $updated_reservation->total_amount_payable ) {
                                                $cash_balance = (round(($updated_reservation->total_amount_payable / $cash_ledger_count),2) * $cash_ledger_count) - $cash_amount_paid;
                                            } else {
                                                $cash_balance = 0;
                                            }
                                        }

                                        if( $payment_amount <= $cash_balance ) {
                                            $downpayment_amount = $payment_amount;
                                        } else {
                                            $downpayment_amount = $cash_balance;
                                        }
                                    }

                                    if( $downpayment_amount > 0 )  {
                                        $collection[$index] = new Collection([
                                            $row[0], trim($row[1]), $downpayment_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                        ]);

                                        $payment_amount = $payment_amount - $downpayment_amount;
                                    }

                                    if( $payment_amount <= 0 ) {
                                        break;
                                    }

                                    break;
                                case 'title_fee':
                                    $index = 100;
                                    $remaining_balance = 0;

                                    $title_fee_payment = RealEstatePayment::where('client_number', $client_number)
                                        ->where('payment_type', $ptype)->get();
                                    $title_fee_payment_count = $title_fee_payment->count();


                                    if( $updated_reservation->with_twelve_percent_vat ) {
                                        $title_transfer_amount = $updated_reservation->net_selling_price_with_vat * 0.05;
                                    } else {
                                        $title_transfer_amount = $updated_reservation->net_selling_price * 0.05;
                                    }

                                    if( $title_fee_payment_count > 0 ) {

                                        if( $title_fee_payment[0]->payment_amount < $title_transfer_amount ) {
                                            $remaining_balance = $title_transfer_amount - $title_fee_payment[0]->payment_amount;
                                        }

                                        if( $remaining_balance > 0 ) {

                                            if( ($payment_amount < $title_transfer_amount) ) {
                                                $title_transfer_amount = ($payment_amount > 0 && $payment_amount < $title_transfer_amount ) ? $payment_amount : 0;
                                            }
            
                                            if( $title_transfer_amount > 0 ) {
                                                $collection[$index] = new Collection([
                                                    $row[0], trim($row[1]), $title_transfer_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                                ]);
                                            }
                                        }

                                    } else {

                                        if( ($payment_amount < $title_transfer_amount) ) {
                                            $title_transfer_amount = ($payment_amount > 0 && $payment_amount < $title_transfer_amount ) ? $payment_amount : 0;
                                        }
        
                                        if( $title_transfer_amount > 0 ) {
                                            $collection[$index] = new Collection([
                                                $row[0], trim($row[1]), $title_transfer_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                            ]);
                                        }

                                    }

                                    $payment_amount = $payment_amount - $title_transfer_amount;

                                    break;
                                case 'penalty':
                                    $index = 300;

                                    foreach( $updated_reservation->amortization_schedule as $i => $schedule) {

                                        if( $record['is_collection'] ) {

                                            $dueDate = Carbon::parse($schedule['due_date'])->startOfDay()->addDays(6);
                                            $daysWithTOne = [1, 3, 5, 7, 8, 10, 12];
                                            $addDays = in_array($dueDate->month, $daysWithTOne) ? 31 : 30;
                                            if( $dueDate->month == 2 ) {
                                                $addDays = ($dueDate->isLeapYear() && $dueDate->day < 29) ? 31 : 30;
                                            }
                                            $nexDueDate = Carbon::parse($schedule['due_date'])->startOfDay()->addDays($addDays);
                                            
                                            $paid_at_gte_start_date = Carbon::parse($paid_at)->startOfDay()->gte($dueDate);
                                            $paid_at_lte_next_date = Carbon::parse($paid_at)->startOfDay()->lt($nexDueDate);

                                            if( $paid_at_gte_start_date && $paid_at_lte_next_date && $schedule['date_paid'] == null ) {

                                                $penalties = $schedule->penalties;
                                                $penalty_count = $penalties->count();

                                                if( $penalty_count  > 0 ) {
                                                    foreach( $penalties as $penalty ) {

                                                        if( $penalty['paid_at'] == null ) {
                                                            $d = (float) $penalty->discount;
                                                            $a = (float) $penalty->penalty_amount;
                                                            $penalty_amount_with_discount = round(!is_nan( ($a - ($a * ($d / 100))) ) ? ($a - ($a * ($d / 100))) : 0, 2);

                                                            $penalty_amount = $d > 0 ? $penalty_amount_with_discount : $penalty->penalty_amount;

                                                            if( ($payment_amount < $penalty_amount) ) {
                                                                $penalty_amount = ($payment_amount > 0 && $payment_amount < $penalty_amount ) ? $payment_amount : 0;
                                                            }
                            
                                                            if( $penalty_amount > 0 ) {
                                                                $collection[$index] = new Collection([
                                                                    $row[0], trim($row[1]), $penalty_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                                                ]);
                                                            }

                                                            $payment_amount = $payment_amount - $penalty_amount;

                                                            if( $payment_amount <= 0 ) {
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                                break; // stop loop on amortization schedule
                                            }
                                        }

                                    }
                                    
                                    break;
                                case 'monthly_amortization_payment':
                                    $index = 500;

                                    foreach( $updated_reservation->amortization_schedule as $i => $schedule) {

                                        if( $schedule['is_collection'] ) {

                                            $is_present = Carbon::parse($paid_at)->startOfDay()->lte($schedule['due_date']);

                                            $dueDate = Carbon::parse($schedule['due_date'])->startOfDay();
                                            $daysWithTOne = [1, 3, 5, 7, 8, 10, 12];
                                            $addDays = in_array($dueDate->month, $daysWithTOne) ? 31 : 30;
                                            if( $dueDate->month == 2 ) {
                                                $addDays = ($dueDate->isLeapYear() && $dueDate->day < 29) ? 31 : 30;
                                            }
                                            $nexDueDate = Carbon::parse($schedule['due_date'])->startOfDay()->addDays($addDays);
                                            
                                            $paid_at_gte_start_date = Carbon::parse($paid_at)->startOfDay()->gte($dueDate);
                                            $paid_at_lte_next_date = Carbon::parse($paid_at)->startOfDay()->lt($nexDueDate);

                                            if( $schedule['amount_paid'] !== null && $schedule['amount_paid'] < $schedule['amount'] && $is_present ) {
                                                $schedule['date_paid'] = null;
                                                $schedule['paid_status'] = null;
                                            }

                                            if( $paid_at_gte_start_date && $paid_at_lte_next_date && $schedule['date_paid'] == null ) {

                                                $amortization_amount = $schedule['amount'];
                                                
                                                if( ($payment_amount < $amortization_amount) ) {
                                                    $amortization_amount = ($payment_amount > 0 && $payment_amount < $amortization_amount ) ? $payment_amount : 0;
                                                }
                
                                                if( $amortization_amount > 0 ) {
                                                    $collection[$index] = new Collection([
                                                        $row[0], trim($row[1]), $amortization_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                                    ]);
                                                }

                                                $payment_amount = $payment_amount - $amortization_amount;

                                                if( $payment_amount <= 0 ){
                                                    break;
                                                }
                                            }

                                        }

                                    }
                                    break;
                                case 'docs_fee':
                                    $index = 2000;
                                    $collection[$index] = new Collection([
                                        $row[0], trim($row[1]), $payment_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                    ]);

                                    $payment_amount = 0;

                                    break;
                                case 'redocs_fee':
                                    $index = 2001;
                                    $collection[$index] = new Collection([
                                        $row[0], trim($row[1]), $payment_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                    ]);

                                    $payment_amount = 0;

                                    break;
                                case 'hoa_fees':
                                    $index = 3001;
                                    $collection[$index] = new Collection([
                                        $row[0], trim($row[1]), $payment_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                    ]);

                                    $payment_amount = 0;

                                    break;
                                case 'others':
                                    $index = 4001;
                                    $collection[$index] = new Collection([
                                        $row[0], trim($row[1]), $payment_amount, $client_number, $first_name, $last_name, $ptype, $payment_gateway
                                    ]);

                                    $payment_amount = 0;

                                    break;
                                default:
                                    break;
                            }

                        }

                        // Arrage collection/payment based on priorities
                        $bulk_payments = $collection->all();
                        ksort($bulk_payments);
                        $bulk_payments = new Collection($bulk_payments);

                        if( $payment_amount > 0 ) {

                            if( $reservation->payment_terms_type == 'in_house' && isset($bulk_payments[500]) ) {
                                if( $bulk_payments[500][6] === 'monthly_amortization_payment' ) {
                                    $bulk_payments[500][2] = $bulk_payments[500][2] + $payment_amount;
                                }
                            }

                            if( $reservation->payment_terms_type == 'cash' && isset($bulk_payments[3]) ) {
                                if( $bulk_payments[3][6] === 'downpayment' || 
                                    $bulk_payments[3][6] === 'split_cash' || 
                                    $bulk_payments[3][6] === 'partial_cash' || 
                                    $bulk_payments[3][6] === 'full_cash' ) 
                                {
                                    $bulk_payments[3][2] = $bulk_payments[3][2] + $payment_amount;
                                }
                            }
                        }

                        $this->collection($bulk_payments);

                    } else {

                        $generateTransactionID = Str::upper(Str::random(10));
                        // Creates a new reference number if it encounters duplicate
                        while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
                            $generateTransactionID = Str::upper(Str::random(10));
                        }

                        $data = [
                            'transaction_id' => $generateTransactionID,
                            'reservation_number' => null,
                            'paid_at' => $paid_at,
                            'payment_amount' => $payment_amount,
                            'payment_gateway' => $payment_gateway,
                            'payment_type' => $payment_type,
                            'user' => $this->user,
                            'client_number' => $client_number,
                            'first_name' => $first_name,
                            'last_name' => $last_name
                        ];
                        $this->add_payment_request($data);

                    }
                    
                } else {

                    if( $payment_amount !== null && $client_number !== null && $first_name !== null && $last_name !== null && $payment_type !== null  ) {

                        $reservation = Reservation::where('client_number', $client_number)
                            ->with('payment_details')
                            ->first();

                        if( $reservation ) {
                        
                            if( $reservation['payment_terms_type'] === 'cash' && $payment_type === 'downpayment' ) {
                                $payment_type = ($reservation['split_cash']) ? 'split_cash' : 'full_cash';
                            }
        
                            // Prepare client data for checking
                            if( !isset($client_data[$client_number]) ) {
                                $client_data[$client_number] = [];
                                // $client_data[$client_number]['payment_types'] = [];
                                $client_data[$client_number]['existing_payments'] = [];
                                $client_data[$client_number]['report_counter'] = 0;
                            }
        
                            if( count($reservation->payment_details) > 0 ) {
                                $client_data[$client_number]['existing_payments'][$payment_type] = [];
                                $is_completed = true;
                                foreach( $reservation->payment_details as $k => $value ) {

                                    if( $payment_type === 'split_cash' &&  $value['payment_type'] == $payment_type ) {
                                        $cash_ledger = CashTermLedger::where('transaction_id', $value['transaction_id'])->first();
                                        $is_completed = ($value['payment_amount'] >= $cash_ledger->amount) ? true : false;
                                    }

                                    if( $payment_type === 'downpayment' &&  $value['payment_type'] == $payment_type ) {
                                        $res_downpayment_amount = ($reservation->split_downpayment) ? round($reservation->split_downpayment_amount, 2) : round($reservation->downpayment_amount, 2);
                                        $is_completed = ($value['payment_amount'] >= $res_downpayment_amount) ? true : false;
                                    }

                                    if( $payment_type === 'reservation_fee_payment' &&  $value['payment_type'] == $payment_type ) {
                                        $reservation_fee_amount = $reservation->reservation_fee_amount;
                                        $is_completed = ($value['payment_amount'] >= $reservation_fee_amount) ? true : false;
                                    }

                                    if( $payment_type === 'retention_fee' &&  $value['payment_type'] == $payment_type ) {
                                        if($reservation->with_five_percent_retention_fee) {
                                            $retention_fee_amount = $reservation->retention_fee;
                                            $is_completed = ($value['payment_amount'] >= $reservation_fee_amount) ? true : false;
                                        }
                                    }

                                    if( $payment_type === 'title_fee' &&  $value['payment_type'] == $payment_type ) {
                                        $contract_amount = $reservation->with_twelve_percent_vat ? $reservation->net_selling_price_with_vat : $reservation->net_selling_price;
                                        $title_fee_amount = $contract_amount * 0.05;
                                        $is_completed = ($value['payment_amount'] >= $title_fee_amount) ? true : false;
                                    }

                                    if( $payment_type === 'docs_fee' &&  $value['payment_type'] == $payment_type ) {
                                        $is_completed = false;
                                    }

                                    if( $payment_type === 'redocs_fee' &&  $value['payment_type'] == $payment_type ) {
                                        $is_completed = false;
                                    }

                                    if( $payment_type === 'hoa_fees' &&  $value['payment_type'] == $payment_type ) {
                                        $is_completed = false;
                                    }

                                    if( $payment_type === 'others' &&  $value['payment_type'] == $payment_type ) {
                                        $is_completed = false;
                                    }
                                    
                                    if(isset($client_data[$client_number]['existing_payments'][$reservation->payment_details[$k]['payment_type']]) && $is_completed){
                                        $client_data[$client_number]['existing_payments'][$reservation->payment_details[$k]['payment_type']][] = $reservation->payment_details[$k]['payment_type'];
                                    }
                                }
                            } else {
                                $client_data[$client_number]['existing_payments'][$payment_type] = [];
                            }

    
                            $generateTransactionID = Str::upper(Str::random(10));
                            // Creates a new reference number if it encounters duplicate
                            while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
                                $generateTransactionID = Str::upper(Str::random(10));
                            }
    
                            $reservation_number = $reservation['reservation_number'];
    
                            if( !isset($reports[$client_number]['report_counter']) ) {
                                $reports[$client_number]['report_counter'] = 0;
                            }
                            
                            $reports[$client_number]['reservation_number'] = $reservation_number;
    
                            $params = [
                                'transaction_id' => $generateTransactionID,
                                'reservation_number' => $reservation_number,
                                'client_number' => $client_number,
                                'first_name' => $first_name,
                                'middle_name' => null,
                                'last_name' => $last_name,
                                'email' => '',
                                'contact_number' => '',
                                'sales_agent' => '',
                                'sales_manager' => '',
                                'currency' => 'PHP',
                                'payment_amount' => $payment_amount,
                                'payment_gateway' => $payment_gateway,
                                'payment_type' => $payment_type,
                                'payment_encode_type' => 'admin',
                                'payment_gateway_reference_number' => null,
                                'remarks' => null,
                                'discount' => 0,
                                'paid_at' => $paid_at,
                                'bank' => null,
                                'bank_account_number' => null,
                                'cr_number' => null,
                                'or_number' => null,
                                'is_verified' => 1,
                                'verified_date' => null,
                                'verified_by' => null,
                                'advance_payment' => 0,
                                'check_number' => null,
                                'status' => 'SUCCESS_ADMIN',
                                'message' => 'Paid via bulk upload.'
                            ];
    
                            /* Reservation, Title Fee, Docs Fee and Redocs Fee Payment Validation
                            * Check if payment is exists
                            * 1 reservation/title fee payment per reservation
                            */
                            if( $payment_type === 'reservation_fee_payment' || $payment_type === 'title_fee' || $payment_type === 'docs_fee' || $payment_type === 'redocs_fee' || $payment_type === 'hoa_fees' || $payment_type === 'others' ) {
                                if( count($client_data[$client_number]['existing_payments'][$payment_type]) <= 0 ) {

                                    $data = [
                                        'transaction_id' => $generateTransactionID,
                                        'reservation_number' => $reservation_number,
                                        'paid_at' => $paid_at,
                                        'payment_amount' => $payment_amount,
                                        'payment_gateway' => $payment_gateway,
                                        'payment_type' => $payment_type,
                                        'user' => $this->user,
                                        'first_name' => $first_name,
                                        'last_name' => $last_name,
                                    ];

                                    if( isset($row[13]) ) {
                                        $this->record_type = $row[13]; 
                                    }

                                    $this->add_payment_request($data);

                                    // $this->insert_payment_and_statuses($params);
                                } else {
                                    $message = [
                                        'reservation_fee_payment' => 'Multiple Reservation Fee Entry',
                                        'title_fee' => 'Multiple Title Fee Entry',
                                        'redocs_fee' => 'Multiple Redocumentation Fee Entry',
                                        'docs_fee' => 'Multiple Documentation Fee Entry'
                                    ];
                                    $reports[$client_number][$message[$payment_type]][] = [
                                        'data' => $rows[$key]
                                    ];
                                    $reports[$client_number]['report_counter']++;
                                }
                            }
    
                            /* Retention Fee Validation
                            * Check if payment is exists
                            * Allowed only 1 payment per reservation
                            */
                            if( $payment_type === 'retention_fee' ) {
                                if( $reservation['payment_terms_type'] === 'cash' ) {
                                    if( count($client_data[$client_number]['existing_payments'][$payment_type]) <= 0 ) {

                                        $data = [
                                            'transaction_id' => $generateTransactionID,
                                            'reservation_number' => $reservation_number,
                                            'paid_at' => $paid_at,
                                            'payment_amount' => $payment_amount,
                                            'payment_gateway' => $payment_gateway,
                                            'payment_type' => $payment_type,
                                            'user' => $this->user,
                                            'first_name' => $first_name,
                                            'last_name' => $last_name,
                                        ];

                                        if( isset($row[13]) ) {
                                            $this->record_type = $row[13]; 
                                        }
    
                                        $this->add_payment_request($data);

                                        // $this->insert_payment_and_statuses($params);
                                    } else {
                                        $reports[$client_number]['Multiple Retention Fee Entry'][] = [
                                            'data' => $rows[$key]
                                        ];
                                    }
                                } else {
                                    $reports[$client_number]['Not a Cash Term Type'][] = [
                                        'data' => $rows[$key]
                                    ];
                                    $reports[$client_number]['report_counter']++;
                                }
                            }
    
                            /* Downpayment Validation | In House term | Cash Term
                            * Check if number of downpayment splits is less than the downpayment record
                            * Allowed downpayment entry based on the number of splits, if not split downpayment allowed only 1 entry
                            */
                            if( $payment_type === 'downpayment' ) {
                                if( $reservation['split_downpayment'] ) {
                                    if( count($client_data[$client_number]['existing_payments'][$payment_type]) < $reservation['number_of_downpayment_splits'] ) {
                                        $data = [
                                            'transaction_id' => $generateTransactionID,
                                            'reservation_number' => $reservation_number,
                                            'paid_at' => $paid_at,
                                            'payment_amount' => $payment_amount,
                                            'payment_gateway' => $payment_gateway,
                                            'payment_type' => $payment_type,
                                            'user' => $this->user,
                                            'first_name' => $first_name,
                                            'last_name' => $last_name,
                                        ];

                                        if( isset($row[13]) ) {
                                            $this->record_type = $row[13]; 
                                        }

                                        $this->add_payment_request($data);
                                    } else {
                                        $reports[$client_number]['Downpayment Entry is higher than the number of downpayment splits'][] = [
                                            'data' => $rows[$key]
                                        ];
                                        $reports[$client_number]['report_counter']++;
                                    }
                                } else {

                                    if( count($client_data[$client_number]['existing_payments'][$payment_type]) <= 0 ) {

                                        $data = [
                                            'transaction_id' => $generateTransactionID,
                                            'reservation_number' => $reservation_number,
                                            'paid_at' => $paid_at,
                                            'payment_amount' => $payment_amount,
                                            'payment_gateway' => $payment_gateway,
                                            'payment_type' => $payment_type,
                                            'user' => $this->user,
                                            'first_name' => $first_name,
                                            'last_name' => $last_name,
                                        ];

                                        if( isset($row[13]) ) {
                                            $this->record_type = $row[13]; 
                                        }

                                        $this->add_payment_request($data);

                                        // $this->insert_payment_and_statuses($params);
                                    } else {
                                        $reports[$client_number]['None split downpayment have multiple payment entry'][] = [
                                            'data' => $rows[$key]
                                        ];
                                        $reports[$client_number]['report_counter']++;
                                    }
                                }
                            }
    
                            /* Split Cash Validation | Cash Terms 
                            * Check if number of splits is less than the split record
                            * Allowed payment based on the number of cash splits, if not split cash allowed only 1 entry
                            */
                            if( $payment_type === 'split_cash' || $payment_type === 'full_cash' || $payment_type === 'partial_cash' ) {

                                if( $reservation['split_cash'] ) {

                                    if( count($client_data[$client_number]['existing_payments'][$payment_type]) < $reservation['number_of_cash_splits'] ) {
                                        $data = [
                                            'transaction_id' => $generateTransactionID,
                                            'reservation_number' => $reservation_number,
                                            'paid_at' => $paid_at,
                                            'payment_amount' => $payment_amount,
                                            'payment_gateway' => $payment_gateway,
                                            'payment_type' => $payment_type,
                                            'user' => $this->user,
                                            'first_name' => $first_name,
                                            'last_name' => $last_name,
                                        ];

                                        if( isset($row[13]) ) {
                                            $this->record_type = $row[13]; 
                                        }

                                        $this->add_payment_request($data);
                                    } else {
                                        $reports[$client_number]['Split Cash Entry is Higher Than The Number Of Cash splits'][] = [
                                            'data' => $rows[$key]
                                        ];
                                        $reports[$client_number]['report_counter']++;
                                    }
                                } else {
                                    if( count($client_data[$client_number]['existing_payments'][$payment_type]) <= 0 ) {
                                        if ( $payment_type === 'full_cash' ) {
                                            $payment_type = ($payment_amount < $reservation->total_amount_payable) ? 'partial_cash' : $payment_type;
                                        }
                                        $data = [
                                            'transaction_id' => $generateTransactionID,
                                            'reservation_number' => $reservation_number,
                                            'paid_at' => $paid_at,
                                            'payment_amount' => $payment_amount,
                                            'payment_gateway' => $payment_gateway,
                                            'payment_type' => $payment_type,
                                            'user' => $this->user,
                                            'first_name' => $first_name,
                                            'last_name' => $last_name,
                                        ];

                                        if( isset($row[13]) ) {
                                            $this->record_type = $row[13]; 
                                        }

                                        $this->add_payment_request($data);
                                    } else {
                                        $reports[$client_number]['None Split Cash Have Multiple Payment Entry'][] = [
                                            'data' => $rows[$key]
                                        ];
                                        $reports[$client_number]['report_counter']++;
                                    }
                                }
    
                            }
    
                            /* Amortization Payments
                            * Bypass payment request validation
                            * Run through addPayment api
                            **/
                            if( ($payment_type === 'monthly_amortization_payment' || $payment_type === 'penalty') && $reservation['payment_terms_type'] == 'in_house' ) {
                                $data = [
                                    'transaction_id' => $generateTransactionID,
                                    'reservation_number' => $reservation_number,
                                    'paid_at' => $paid_at,
                                    'payment_amount' => $payment_amount,
                                    'payment_gateway' => $payment_gateway,
                                    'payment_type' => $payment_type,
                                    'user' => $this->user,
                                ];

                                if( $this->recompute == true ) {
                                    $data['bank'] = $row[8];
                                    $data['check_number'] = $row[9];
                                    $data['bank_account_number'] = $row[10];
                                    $data['or_number'] = $row[11];
                                    $data['cr_number'] = $row[12];
                                    if( isset($row[13]) ) {
                                        $this->record_type = $row[13];
                                    }
                                    $data['payment_gateway_reference_number'] = $row[15];
                                    $data['remarks'] = $row[16];
                                    $data['payment_encode_type'] = $row[17];
                                    $data['penalty_transaction_details'] = $row[18];
                                    $data['amortization_transaction_details'] = $row[19];
                                }

                                $this->amortization_request($data);
                            }

                            if( $payment_type === 'penalty' && $reservation['payment_terms_type'] == 'cash' ) {
                                $data = [
                                    'payment_terms_type' => 'cash',
                                    'transaction_id' => $generateTransactionID,
                                    'reservation_number' => $reservation_number,
                                    'paid_at' => $paid_at,
                                    'payment_amount' => $payment_amount,
                                    'payment_gateway' => $payment_gateway,
                                    'payment_type' => $payment_type,
                                    'user' => $this->user,
                                ];

                                $this->cash_ledger_request($data);
                            }
    
                        } else {

                            $generateTransactionID = Str::upper(Str::random(10));
                            // Creates a new reference number if it encounters duplicate
                            while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
                                $generateTransactionID = Str::upper(Str::random(10));
                            }

                            $data = [
                                'transaction_id' => $generateTransactionID,
                                'reservation_number' => null,
                                'paid_at' => $paid_at,
                                'payment_amount' => $payment_amount,
                                'payment_gateway' => $payment_gateway,
                                'payment_type' => $payment_type,
                                'user' => $this->user,
                                'client_number' => $client_number,
                                'first_name' => $first_name,
                                'last_name' => $last_name,
                            ];

                            if( isset($row[13]) ) {
                                $this->record_type = $row[13]; 
                            }

                            $this->add_payment_request($data);

                            $reports[$client_number]['No Reservation Details'][] = [
                                'data' => $rows[$key]
                            ];
                        }
    
                    } else {

                        $generateTransactionID = Str::upper(Str::random(10));
                        // Creates a new reference number if it encounters duplicate
                        while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
                            $generateTransactionID = Str::upper(Str::random(10));
                        }

                        $data = [
                            'transaction_id' => $generateTransactionID,
                            'reservation_number' => null,
                            'paid_at' => $paid_at,
                            'payment_amount' => $payment_amount,
                            'payment_gateway' => $payment_gateway,
                            'payment_type' => $payment_type,
                            'user' => $this->user,
                            'client_number' => $client_number,
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                        ];

                        if( isset($row[13]) ) {
                            $this->record_type = $row[13]; 
                        }
                        
                        $this->add_payment_request($data);

                    }

                }

            }
        }

        RealestatePaymentActivityLog::create([
            'action' => 'import_payment',
            'description' => 'Import payments',
            'model' => 'App\Models\RealEstate\RealEstatePayment',
            'properties' => null,
            'created_by' => $this->user->id,
        ]);

        $this->reports = $reports;
        return true;
    }

    public function add_payment_request($data)
    {
        $addRequest = new AddPaymentRequest;
        $addRequest->bulk_upload_request = true;
        $addRequest->transaction_id = $data['transaction_id'];
        $addRequest->reservation_number = $data['reservation_number'];
        $addRequest->paid_at = $data['paid_at'];
        $addRequest->payment_amount = $data['payment_amount'];
        $addRequest->payment_gateway = $data['payment_gateway'];
        $addRequest->payment_type = $data['payment_type'];
        $addRequest->pr_number = null;
        $addRequest->or_number = null;
        $addRequest->remarks = null;
        $addRequest->user = $data['user'];
        if( isset( $data['client_number'] ) ) {
            $addRequest->client_number = $data['client_number'];
        }
        if( isset( $data['first_name'] ) ) {
            $addRequest->first_name = $data['first_name'];
        }
        if( isset( $data['last_name'] ) ) {
            $addRequest->last_name = $data['last_name'];
        }
        $addRequest->record_type = $this->record_type;
        $addRequest->recompute = $this->recompute;
        $addPayment = new AddPayment;
        $addPayment->__invoke($addRequest);
    }

    public function amortization_request($data)
    {
        $addRequest = new AddPaymentRequest;
        $addRequest->bulk_upload_request = true;
        $addRequest->transaction_id = $data['transaction_id'];
        $addRequest->reservation_number = $data['reservation_number'];
        $addRequest->paid_at = $data['paid_at'];
        $addRequest->payment_amount = $data['payment_amount'];
        $addRequest->payment_gateway = $data['payment_gateway'];
        $addRequest->payment_type = $data['payment_type'];
        $addRequest->user = $data['user'];

        if( $this->recompute == true ) {
            $addRequest->bank = $data['bank'];
            $addRequest->check_number = $data['check_number'];
            $addRequest->bank_account_number = $data['bank_account_number'];
            $addRequest->or_number = $data['or_number'];
            $addRequest->cr_number = $data['cr_number'];
            $addRequest->payment_gateway_reference_number = $data['payment_gateway_reference_number'];
            $addRequest->remarks = $data['remarks'];
            $addRequest->payment_encode_type = $data['payment_encode_type'];
            $addRequest->penalty_transaction_details = $data['penalty_transaction_details'];
            $addRequest->amortization_transaction_details = $data['amortization_transaction_details'];
        }

        $addRequest->record_type = $this->record_type;
        $addRequest->recompute = $this->recompute;
        $addRequest->waive_penalty = $this->waive_penalty;
        $addPayment = new AddPayment;
        $addPayment->__invoke($addRequest);
    }

    public function cash_ledger_request($data)
    {

        $penalty_record = CashTermPenalty::where('reservation_number', $data['reservation_number'])
            ->whereNull('paid_at')
            ->first();
        
        $addRequest = new AddPaymentRequest;
        $addRequest->bulk_upload_request = true;
        $addRequest->id = $penalty_record->id; 
        $addRequest->discount = 0; 
        $addRequest->penalty_amount = $penalty_record->penalty_amount; 
        $addRequest->amount_paid = $data['payment_amount']; 
        $addRequest->transaction_id = false;
        $addRequest->payment_terms_type = $data['payment_terms_type'];
        $addRequest->paid_at = $data['paid_at'];
        $addRequest->user = $this->user;
        $addPayment = new AddPayment;
        $addPayment->penaltyPayment($addRequest);
    }

    public function insert_payment_and_statuses($params)
    {
        $newPayment = RealEstatePayment::create([
            'transaction_id' => $params['transaction_id'],
            'reservation_number' => $params['reservation_number'],
            'client_number' => $params['client_number'],
            'first_name' => $params['first_name'],
            'middle_name' => $params['middle_name'],
            'last_name' => $params['last_name'],
            'email' => $params['email'],
            'contact_number' => $params['contact_number'],
            'sales_agent' => $params['sales_agent'],
            'sales_manager' => $params['sales_manager'],
            'currency' => $params['currency'],
            'payment_amount' => $params['payment_amount'],
            'payment_gateway' => $params['payment_gateway'],
            'payment_type' => $params['payment_type'],
            'payment_encode_type' => $params['payment_encode_type'],
            'payment_gateway_reference_number' => $params['payment_gateway_reference_number'],
            'remarks' => $params['remarks'],
            'discount' => $params['discount'],
            'paid_at' => $params['paid_at'],
            'bank' => $params['bank'],
            'bank_account_number' => $params['bank_account_number'],
            'cr_number' => $params['cr_number'],
            'or_number' => $params['or_number'],
            'is_verified' => $params['is_verified'],
            'verified_date' => $params['verified_date'],
            'verified_by' => $params['verified_by'],
            'advance_payment' => $params['advance_payment'],
            'check_number' => $params['check_number'],
        ]);

        $newPayment->paymentStatuses()->create([
            'status' => $params['status'],
            'message' => $params['message'],
        ]);

        return $newPayment;
    }
}