<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;
use App\Models\RealEstate\AmortizationPenalty;
use App\Models\RealEstate\AmortizationSchedule;
use App\Http\Requests\RealEstate\AddPaymentRequest;
use App\Models\RealEstate\RealestateActivityLog;
use Carbon\Carbon;

class AmortizationSchedule extends Model
{
    //
    protected $fillable = [
        'reservation_number',
        'number',
        'due_date',
        'amount',
        'date_paid',
        'paid_status',
        'amount_paid',
        'transaction_id',
        'pr_number',
        'or_number',
        'account_number',
        'principal',
        'interest',
        'balance',
        'generated_principal',
        'generated_interest',
        'generated_balance',
        'is_old',
        'is_collection',
        'is_sales',
        'remarks',
        'datetime',
        'excess_payment',
        'type',
    ];

    protected $appends = [
        'penalty_records',
    ];

    public function getPenaltyRecordsAttribute()
    {
        $penalties = AmortizationPenalty::where('reservation_number', $this->reservation_number)
            ->where('amortization_schedule_id', $this->id)->get();

        return ($penalties) ? $penalties : [];
    }


    public function penalties()
    {
        return $this->hasMany('App\Models\RealEstate\AmortizationPenalty', 'reservation_number', 'reservation_number');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\RealEstate\RealEstatePayment', 'amortization_schedule_id', 'id');
    }

    static public function collections($reservation) 
    {
        $data = [];
        $date = Carbon::now();
        $schedules = AmortizationSchedule::where([
            'reservation_number' => $reservation->reservation_number,
            'is_collection' => 1
        ])->with('penalties')->with('payments')->orderBy('number', 'ASC')->orderBy('id', 'ASC')->get();

        $in_house_balance = (float) $reservation->total_balance_in_house;
        $default_discount = $reservation->default_penalty_discount_percentage;
        $penaltyComputed = 0;
        $balance_with_penalty = 0;
        $currentBalance = 0;
        $prev_balance = 0;
        $totalAmount = 0;
        $totalPenalty = 0;
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

        foreach( $schedules as $key => $schedule  ) {

            $amortization_sched_id = $schedule['id'];

            // Migration of old amortization record for new implementation
            if( $schedule->is_old == 1 ) {
                $balance = $schedule['balance'];
                $principal = $schedule['principal'];
                $interest = $schedule['interest'];
                
                AmortizationSchedule::where('id', $amortization_sched_id)
                    ->where('is_sales', 1)
                    ->update([
                        'generated_balance' => $balance,
                        'generated_principal' => $principal,
                        'generated_interest' => $interest,
                        'is_old' => 0,
                    ]);

                AmortizationSchedule::where('id', $amortization_sched_id)
                    ->whereNull('amount_paid')
                    ->whereNull('paid_status')
                    ->whereNull('date_paid')
                    ->update([
                        'balance' => 0,
                        'principal' => 0,
                        'interest' => 0,
                    ]);
            }

            $penaltyComputed = self::automate_penalty($schedule, [
                'date_to_check' => (  $schedule['date_paid'] !== null ) ? Carbon::parse($schedule['date_paid']) : Carbon::now(),
                'penaltyComputed' => $penaltyComputed,
                'user_id' => $reservation['request_user_id']
            ]);

            if( $schedule['balance'] != 0 ) {
                $currentBalance = $schedule['balance'];
            }

            $currentBalance = self::getCurrentBalance($schedule, $currentBalance, $in_house_balance, $view = true, $default_discount, $balance_with_penalty);

            $currentPenalty = $currentBalance['penalty'];
            $currentBalanceWithPenalty = $currentBalance['balance_with_penalty'];
            $currentPenaltyStatus = $currentBalance['penalty_status'];
            $currentBalance = $currentBalance['value'];
            
            if( $currentBalance != 0 && $schedule['date_paid'] !== null) {
                $prev_balance = $currentBalance;
            }

            if( $key == 0 ) {
                $currentBalance = $currentBalance == 0 ? $in_house_balance : $currentBalance;
                $prev_balance = $currentBalance;
            }

            if( $schedule['date_paid'] === null && $key != 0 ) {
                $currentBalance = $prev_balance;
            }

            $schedule['balance'] = $currentBalance;

            // if( is_null($schedule['date_paid']) && !in_array($currentPenaltyStatus, ['waived', 'waived_wp', 'paid']) && $currentPenalty > 0 ) {
            //     $totalAmount = $totalAmount + $schedule['amount'];
            //     $totalPenalty = $totalPenalty + $currentPenalty;
            //     $balance_with_penalty =  $schedule['amount'] + $totalPenalty;
            //     $schedule['balance_with_penalty'] = $balance_with_penalty;
            //     $schedule['merged_penalty_amount'] = $totalPenalty;
            // } else {
            //     $schedule['balance_with_penalty'] = 0;
            //     $schedule['merged_penalty_amount'] = 0;
            // }

            if( is_null($schedule['date_paid']) && !in_array($currentPenaltyStatus, ['waived', 'waived_wp', 'paid']) && $currentPenalty > 0 ) {
                $schedule['balance_with_penalty'] = $currentBalanceWithPenalty;
                $balance_with_penalty = $schedule['balance_with_penalty'];
                $schedule['computed_penalty_amount'] = $currentPenalty;
            } else {
                $schedule['computed_penalty_amount'] = 0;
                $schedule['balance_with_penalty'] = 0;
                $balance_with_penalty = 0;
            }

            // $schedule['balance_with_penalty'] = ( $key !== 0 ) ? $schedule['amount'] + $currentPenalty : $schedule['amount'] + $currentPenalty;
            $data[] = $schedule;
        }
        
        return $data;
    }

    static public function getCurrentBalance($schedule, $currentBalance, $in_house_balance, $view = false, $default_discount = 0, $balance_with_penalty = false)
    {
        $date_paid  = $schedule['date_paid'];
        $principal = (float) $schedule['principal'];
        $amount = (float) $schedule['amount'];
        $balance = (float) $schedule['balance'];
        $schedule['penalty_status'] = null;
        $schedule['penalty_is_paid'] = null;
        $schedule['penalty'] = 0;
        $penalty = $amount * 0.03;
        $is_past = Carbon::now()->startOfDay()->gte($schedule['due_date']);
        $fp = 0;
        $penalty_discount = 0;

        // if( $view ) {
        //     $currentBalance = ($balance > 0) ? $balance : 0;
        // }

        if( $schedule['number'] != '1' && !is_null($schedule['date_paid']) && $balance <= 0 ) {
            $currentBalance = 0;
        }
        
        $currentBalance = ( $currentBalance != 0 ) ? $currentBalance : $balance;
        $currentBalance = ($currentBalance == 0 && $date_paid === null && $schedule['number'] == '1') ? $in_house_balance : $currentBalance;

        $penalty_records = AmortizationPenalty::where('reservation_number', $schedule['reservation_number'])->where('amortization_schedule_id', $schedule['id'])->get();

        // paid amortization
        if( $date_paid !== null ) {
            $pfp = 0;
            foreach( $penalty_records as $key => $penalty_record ) {

                $default_penalty_discount = ( is_null($penalty_record['paid_at']) && $default_discount != 0 ) ? $default_discount : $penalty_record['discount'];
                $penalty_discount = ( $penalty_record['discount'] > 0 ) ? $penalty_record['discount'] : $default_penalty_discount;

                $penalty_amount = (float) $penalty_record['penalty_amount'];

                $penalty_amount_with_discount = round(!is_nan( ($penalty_amount - ($penalty_amount * ($penalty_discount / 100))) ) ? ($penalty_amount - ($penalty_amount * ($penalty_discount / 100))) : 0, 2);

                if( $penalty_amount_with_discount > 0 ) {
                    $penalty_amount = $penalty_amount_with_discount;
                }
                $pfp = $penalty_amount + $pfp;
                $schedule['penalty_status'] = is_null($penalty_record['paid_at']) ? 'unpaid' : 'paid';
                $schedule['penalty_status'] = is_null($penalty_record['status']) ? $schedule['penalty_status'] : 'waived';
                $schedule['penalty_is_paid'] = ($penalty_record['paid_at'] != null) ? true :false;
            };

            if( $pfp > 0 ) {
                $schedule['penalty'] = $pfp;
                // $schedule['balance_with_penalty'] = round($currentBalance + $principal + $pfp, 2); 
            }
        }
        
        // not paid + past in due date + zero balance
        if( $date_paid == null && $is_past && ($balance === 0.00 || $balance == 0 ) ) {

            foreach( $penalty_records as $key => $penalty_record ) {

                $default_penalty_discount = ( is_null($penalty_record['paid_at']) && $default_discount != 0 ) ? $default_discount : $penalty_record['discount'];
                $penalty_discount = ( $penalty_record['discount'] > 0 ) ? $penalty_record['discount'] : $default_penalty_discount;

                $penalty_amount = (float) $penalty_record['penalty_amount'];

                $penalty_amount_with_discount = round(!is_nan( ($penalty_amount - ($penalty_amount * ($penalty_discount / 100))) ) ? ($penalty_amount - ($penalty_amount * ($penalty_discount / 100))) : 0, 2);

                if( $penalty_amount_with_discount > 0 ) {
                    $penalty_amount = $penalty_amount_with_discount;
                }

                $fp = $penalty_amount + $fp;
                $schedule['penalty_status'] = is_null($penalty_record['paid_at']) ? 'unpaid' : 'paid';
                $schedule['penalty_status'] = is_null($penalty_record['status']) ? $schedule['penalty_status'] : 'waived';
                $schedule['penalty_is_paid'] = ($penalty_record['paid_at'] != null) ? true :false;
            };

            if( $fp > 0 ) {
                $schedule['penalty'] = $fp;
                // $schedule['balance_with_penalty'] = round($currentBalance + $fp, 2); 
            }
        }

        // not paid + not past in due date + zero balance
        if( $date_paid === null && !$is_past && ( $balance === 0.00 || $balance == 0 ) ) {

            foreach( $penalty_records as $key => $penalty_record ) {

                $default_penalty_discount = ( is_null($penalty_record['paid_at']) && $default_discount != 0 ) ? $default_discount : $penalty_record['discount'];
                $penalty_discount = ( $penalty_record['discount'] > 0 ) ? $penalty_record['discount'] : $default_penalty_discount;

                $penalty_amount = (float) $penalty_record['penalty_amount'];

                $penalty_amount_with_discount = round(!is_nan( ($penalty_amount - ($penalty_amount * ($penalty_discount / 100))) ) ? ($penalty_amount - ($penalty_amount * ($penalty_discount / 100))) : 0, 2);

                if( $penalty_amount_with_discount > 0 ) {
                    $penalty_amount = $penalty_amount_with_discount;
                }
                $fp = $penalty_amount + $fp;
                $schedule['penalty_status'] = is_null($penalty_record['paid_at']) ? 'unpaid' : 'paid';
                $schedule['penalty_status'] = is_null($penalty_record['status']) ? $schedule['penalty_status'] : 'waived';
                $schedule['penalty_is_paid'] = ($penalty_record['paid_at'] != null) ? true :false;
            };

            if( $fp > 0 ) {
                $schedule['penalty'] = $fp;
                // $schedule['balance_with_penalty'] = round($currentBalance + $fp, 2);
            }
        }
        
        // $currentBalance =  round($currentBalance + $fp, 2);
        $currentBalance =  round($currentBalance, 2);

        if( $balance_with_penalty !== false ) {
            if( $balance_with_penalty == 0 ) {
                $balance_with_penalty = $schedule['amount'] + $schedule['penalty'];
            } else {
                $balance_with_penalty = $balance_with_penalty + $schedule['amount'] + $schedule['penalty'];
            }
        }

        return [
            'value' => $currentBalance, 
            'penalty' => $schedule['penalty'],
            'penalty_discount' => $penalty_discount,
            'balance_with_penalty' => $balance_with_penalty,
            'penalty_status' => $schedule['penalty_status'],
            'penalty_id' => $schedule['penalty_id'],
            'penalty_is_paid' => $schedule['penalty_is_paid']
        ];
    }

    static public function automate_penalty($record, $params) 
    {
        $grace_period = Carbon::parse($record['due_date'])->endOfDay()->addDays(5);
        $is_past = $params['date_to_check']->startOfDay()->gt($record['due_date']);
        $penalty_record = AmortizationPenalty::where('number', $record['number'])
            ->where( 'reservation_number', $record['reservation_number'])->first();

        if( $penalty_record ) {
            if( isset($penalty_record->status) ){
                $penalty_status = $penalty_record->status;
            } else {
                $penalty_status = NULL;
            }
        } else {
            $penalty_status = false;
        }

        if( $is_past && 
            !AmortizationPenalty::where(['number' => $record['number'], 'reservation_number' => $record['reservation_number']])->exists() && 
            ( is_null($penalty_status) || $penalty_status == false ) &&
            $record['date_paid'] == null 
        ) {

            if( !is_null($record['date_paid']) && $record->penalties->count() > 0 ) {

                if( $params['penaltyComputed'] <= 0 ) {
                    $params['penaltyComputed'] = $record['amount'] + ($record['amount'] * 0.03);
                } else {
                    $params['penaltyComputed'] = ($record['amount'] + ($params['penaltyComputed'] * 0.03)) + $params['penaltyComputed'];
                }

            }

            if( $params['penaltyComputed'] <= 0 ) {
                $additional_penalty = $record['amount'] * 0.03;
                $params['penaltyComputed'] = $record['amount'] + $additional_penalty;
            } else {
                $additional_penalty = $params['penaltyComputed'] * 0.03;
                $params['penaltyComputed'] =  $record['amount'] + $additional_penalty + $params['penaltyComputed'];
            }
            
            AmortizationPenalty::create([
                'reservation_number' => $record['reservation_number'],
                'amortization_schedule_id' => $record['id'],
                'number' => $record['number'],
                'penalty_amount' => $additional_penalty,
                'type' => 'amortization_penalty',
                'system_generated' => 1,
                'is_old' => 0
            ]);

            // RealestateActivityLog::create([
            //     'reservation_number' => $record['reservation_number'],
            //     'action' => 'add_penalty',
            //     'description' => 'System Generated 3% penalty in Amortization ' . $record['number'] . ' with amount of ' . round($additional_penalty, 2),
            //     'model' => 'App\Models\SalesAdminPortal\Reservation',
            //     'properties' => null,
            //     'created_by' => $params['user_id'],
            // ]);

        }

        return $params['penaltyComputed'];
    }

    public function reset_amortization_schedule($reservation_number)
    {
        AmortizationSchedule::where('reservation_number', $reservation_number)
            ->update([
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

        AmortizationSchedule::where('reservation_number', $reservation_number)
            ->where('is_sales', 0)->delete();

        AmortizationPenalty::where('reservation_number', $reservation_number)
            ->whereNotNull('amount_paid')->delete();
    }
}
