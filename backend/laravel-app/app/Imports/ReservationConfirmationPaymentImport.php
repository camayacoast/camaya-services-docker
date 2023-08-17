<?php

namespace App\Imports;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\RealEstatePayment;
use App\Models\RealEstate\RealEstatePaymentStatus;
use App\Http\Controllers\SalesAdminPortal\AddPayment;
use App\Models\RealEstate\AmortizationSchedule;
use App\Models\RealEstate\CashTermPenalty;
use App\Models\RealEstate\CashTermLedger;
use App\Http\Requests\RealEstate\AddPaymentRequest;
use App\Models\RealEstate\RealestatePaymentActivityLog;
use App\User;
use Illuminate\Support\Str;

use Carbon\Carbon;

class ReservationConfirmationPaymentImport implements ToCollection
{

    public $reports;
    public $user;
    public $created_old_reservation_number = [];
    public $old_record_lists = [];

    public function collection(Collection $rows)
    {
        $allowed_payment_gateway = [
            'Cash', 'PDC', 'Direct Payment', 'Card Transaction'
        ];
        $client_data = ['no_ra' => []];
        $reports = [
            'no_reservation_lists' => [
                'data' => [],
                'count' => 0,
            ],
            'ok_lists' => [
                'data' => [],
                'count' => 0,
            ],
            'ok_display' => [
                'data' => [],
                'count' => 0,
            ],
            'not_ok_lists' => [
                'data' => [],
                'message' => [],
                'count' => 0,
            ],
            'not_ok_display' => [
                'data' => [],
                'count' => 0,
            ]
        ];
        $ok_lists = [];
        $not_ok_lists = [];
        $no_reservation_lists = [];

        // Checking of existing payments
        foreach( $rows as $key => $row ) {
            if( $key !== 0 ) {

                $payment_amount = $row[2];
                $client_number = $row[3];
                $first_name = $row[4];
                $last_name = $row[5];
                $payment_type = $row[6];
                $payment_gateway = $row[7];

                if( $payment_amount !== null && $client_number !== null && $first_name !== null && $last_name !== null && $payment_type !== null && $payment_gateway !== null  ) {

                    $reservation = Reservation::where('client_number', $client_number)->with('payment_details')->first();

                    if( $reservation ) {

                        if( $reservation['payment_terms_type'] === 'cash' && $payment_type === 'downpayment' ) {
                            $payment_type = ($reservation['split_cash']) ? 'split_cash' : 'full_cash';
                        }

                        // Prepare client data for checking
                        if( !isset($client_data[$client_number]) ) {
                            $client_data[$client_number] = [];
                            $client_data[$client_number]['payments'] = [];
                        }

                        if( count($reservation->payment_details) > 0 ) {

                            $payment_types = array_map('trim', explode(',', $payment_type));

                            foreach( $payment_types as $payment_type ){

                                foreach( $reservation->payment_details as $k => $value ) {

                                    if( !isset($client_data[$client_number]['payments'][$payment_type]) ) {
                                        $client_data[$client_number]['payments'][$payment_type] = 0;
                                    }

                                    $is_completed = false;

                                    if( $payment_type === 'split_cash' &&  $value['payment_type'] == $payment_type ) {
                                        $cash_ledger = CashTermLedger::where('transaction_id', $value['transaction_id'])->first();
                                        $is_completed = ($value['payment_amount'] >= $cash_ledger->amount) ? true : false;
                                    }

                                    if( $payment_type === 'downpayment' &&  $value['payment_type'] == $payment_type ) {

                                        if( ($reservation->split_downpayment) ) {
                                            $res_downpayment_amount = round($reservation->split_downpayment_amount, 2);
                                        } else {
                                            $res_downpayment_amount = round($reservation->downpayment_amount, 2);
                                        }

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

                                    if( $is_completed ) {
                                        $client_data[$client_number]['payments'][$payment_type]++;
                                    }

                                }

                            }

                        } else {

                            if( !isset($client_data[$client_number]['payments'][$payment_type]) ) {
                                $client_data[$client_number]['payments'][$payment_type] = 0;
                            }

                        }

                    } else {

                        if( !isset($client_data['no_ra'][$client_number]) ) {
                            $client_data['no_ra'][$client_number] = $client_number;
                        }

                    }

                }

            }

        }

        // Checking of payment types
        foreach( $rows as $key => $row ) {

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

                if( $payment_amount !== null && $client_number !== null && $first_name !== null && $last_name !== null && $payment_type !== null && $payment_gateway !== null  ) {

                    if( in_array($payment_gateway, $allowed_payment_gateway) ) {

                        if( !isset( $client_data['no_ra'][$client_number] ) ) {

                            $payment_types = array_map('trim', explode(',', $payment_type));
                            $reservation = Reservation::where('client_number', $client_number)
                                ->with(['amortization_schedule' => function($q) {
                                    $q->where('is_collection', 1)
                                        ->whereNotNull('amount_paid')
                                        ->orderBy('number', 'DESC')
                                        ->orderBy('id', 'DESC')
                                        ->limit(1);
                                }])
                                ->with('payment_details')->first();
                            $reservation_term_type = $reservation->payment_terms_type;
                            $with_retention_fee = $reservation->with_five_percent_retention_fee;

                            foreach( $payment_types as $payment_type ) {

                                if( $reservation_term_type === 'cash' && $payment_type === 'downpayment' ) {
                                    $payment_type = ($reservation->split_cash) ? 'split_cash' : 'full_cash';
                                }

                                $payment_count = isset($client_data[$client_number]['payments'][$payment_type]) ? $client_data[$client_number]['payments'][$payment_type] : 0;

                                if( $payment_type === 'reservation_fee_payment' ) {

                                    if( $payment_count > 0 )  {
                                        $not_ok_lists[$key] = 'Already have reservation payment.';
                                    } else {
                                        $ok_lists[$key] = $key;
                                    }

                                }

                                if( $payment_type === 'downpayment' ) {

                                    if( $reservation->split_downpayment ) {
                                        $number_of_splits = $reservation->number_of_downpayment_splits;
                                    } else {
                                        $number_of_splits = 1;
                                    }

                                    if( $payment_count >= $number_of_splits ) {
                                        $not_ok_lists[$key] = 'Already have downpayment transactions.';
                                    } else {
                                        $ok_lists[$key] = $key;
                                    }

                                }

                                if( $payment_type === 'title_fee' ) {

                                    if( $payment_count > 0 ) {
                                        $not_ok_lists[$key] = 'Already have trensfer title fee payment.';
                                    } else {
                                        $ok_lists[$key] = $key;
                                    }

                                }

                                if( $payment_type === 'retention_fee' ) {

                                    if( $reservation_term_type === 'cash' ) {

                                        if( !is_null($with_retention_fee) ) {

                                            if( $payment_count > 0 ) {
                                                $not_ok_lists[$key] = 'Already have retention fee payment.';
                                            } else {
                                                $ok_lists[$key] = $key;
                                            }

                                        } else {
                                            $not_ok_lists[$key] = 'Reservation don\'t have 5% retention fee.';
                                        }

                                    } else {

                                        $not_ok_lists[$key] = 'Non-cash term reservation type.';

                                    }

                                }

                                if( $payment_type === 'split_cash' || $payment_type === 'full_cash' || $payment_type === 'partial_cash' ) {
                                    $ok_lists[$key] = $key;
                                }

                                if( $payment_type === 'monthly_amortization_payment' ) {

                                    if( $reservation_term_type == 'cash' ) {
                                        $not_ok_lists[$key] = 'Non in-house term reservation type.';
                                    } else {
                                        $last_schedule = $reservation->amortization_schedule;

                                        if( $last_schedule->count() > 0 ) {
                                            $balance = $last_schedule[0]->balance;

                                            if( $balance <= 0 ) {
                                                $not_ok_lists[$key] = 'Amortization is already paid.';
                                            } else {
                                                $ok_lists[$key] = $key;
                                            }

                                        } else {
                                            $ok_lists[$key] = $key;
                                        }
                                    }

                                }

                                if( $payment_type === 'penalty' ) {

                                    if( $reservation_term_type === 'cash' ) {
                                        $penalty_record = CashTermPenalty::where('reservation_number', $reservation->reservation_number)
                                            ->whereNull('paid_at')
                                            ->get();
                                        if( $penalty_record->count() <= 0 ) {
                                            $not_ok_lists[$key] = 'No penalty found or penalties are already paid.';
                                        } else {
                                            $ok_lists[$key] = $key;
                                        }
                                    } else {
                                        $ok_lists[$key] = $key;
                                    }

                                }

                                if( $payment_type === 'docs_fee' || $payment_type === 'redocs_fee' || $payment_type == 'hoa_fees' || $payment_type == 'others' ) {
                                    $ok_lists[$key] = $key;
                                }

                            }

                        } else {
                            $ok_lists[$key] = $key;
                            $no_reservation_lists[$client_number] = $key;
                        }

                    } else {
                        $not_ok_lists[$key] = 'Payment gateway is not in the allowed list. ('.implode(', ', $allowed_payment_gateway).')';
                    }

                } else {

                    if( strtotime($paid_at) > 0 ) {
                        if( in_array($payment_gateway, $allowed_payment_gateway) ) {
                            if( is_null($client_number) ) {
                                $ok_lists[$key] = $key;
                            }
                        } else {
                            $not_ok_lists[$key] = 'Payment gateway is not in the allowed list. ('.implode(', ', $allowed_payment_gateway).')';
                        }
                    }

                }
            }
        }

        // Constructing of data for reports
        $ok_counter = 0;
        $ok_data = [];
        foreach($ok_lists as $k) {
            foreach( $rows[$k] as $i => $upload ) {
                $label = $this->getLabel($i);
                $ok_data[$ok_counter][$label] = $upload;
            }

            $reports['ok_display']['data'] = $ok_data;
            $reports['ok_display']['count']++;
            $reports['ok_lists']['data'][] = $rows[$k];
            $reports['ok_lists']['count']++;
            $ok_counter++;
        }

        $not_ok_counter = 0;
        $not_ok_data = [];
        foreach($not_ok_lists as $k => $message) {
            if( !in_array($k, $ok_lists) ) {

                foreach( $rows[$k] as $i => $upload ) {
                    $label = $this->getLabel($i);
                    $not_ok_data[$not_ok_counter][$label] = $upload;
                }
                $not_ok_data[$not_ok_counter]['message'] = $message;

                $reports['not_ok_display']['data'] = $not_ok_data;
                $reports['not_ok_display']['count']++;
                $reports['not_ok_lists']['data'][] = $rows[$k];
                $reports['not_ok_lists']['message'][] = $message;
                $reports['not_ok_lists']['count']++;
                $not_ok_counter++;
            }
        }

        foreach($no_reservation_lists as $client_number => $row_key) {
            $reports['no_reservation_lists']['data'][] = $client_number;
            $reports['no_reservation_lists']['count']++;
        }

        $reports['user'] = $this->user;

        $this->reports = $reports;

        return $this->reports;
    }

    public function getLabel($value)
    {
        switch ($value) {
            case 1:
                $lebel = 'date';
                break;
            case 2:
                $lebel = 'amount';
                break;
            case 3:
                $lebel = 'client_number';
                break;
            case 4:
                $lebel = 'first_name';
                break;
            case 5:
                $lebel = 'last_name';
                break;
            case 6:
                $lebel = 'payment_destination';
                break;
            case 7:
                $lebel = 'payment_gateway';
                break;
            default:
                $lebel = 'number';
                break;
        };

        return $lebel;
    }
}
