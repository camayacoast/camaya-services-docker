<?php 

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

use App\Http\Controllers\SalesAdminPortal\NewReservation;
use Illuminate\Http\Request;

use App\Models\RealEstate\CashTermPenalty;
use App\Models\RealEstate\RealestateActivityLog;


class CashTermLedger extends Model
{
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
        'remarks',
        'datetime'
    ];

    public function penalties()
    {
        return $this->hasMany('App\Models\RealEstate\CashTermPenalty', 'cash_term_ledger_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\RealEstate\RealEstatePayment', 'transaction_id', 'transaction_id');
    }

    static public function collections($reservation) 
    {
        $data = [];
        $date = Carbon::now();
        $ledgers = CashTermLedger::where([
            'reservation_number' => $reservation->reservation_number
        ])->with('penalties')->with('payments')->get();
        $ledger_count = $ledgers->count();

        $in_house_balance = (float) $reservation->total_balance_in_house;
        $currentBalance = 0;
        $prev_balance = 0;

        if( $ledger_count <= 0 && $reservation->payment_terms_type === 'cash' ) {
            $new_reservation = new NewReservation;
            $request = (object)[];
            $request->payment_terms_type = $reservation->payment_terms_type;
            $request->split_cash = $reservation->split_cash;
            $request->cash_split_number = $reservation->number_of_cash_splits;
            $request->split_cash_end_date = $reservation->split_cash_end_date;
            $request->split_cash_start_date = $reservation->split_cash_start_date;
            $request->reservation_fee_date = $reservation->reservation_fee_date;
            $new_reservation->cash_term_ledger($request, $reservation->reservation_number, $reservation);

            $ledgers = CashTermLedger::where([
                'reservation_number' => $reservation->reservation_number
            ])->with('penalties')->with('payments')->get();
        }

        foreach( $ledgers as $key => $ledger ) {

            $cash_term_ledger_id = $ledger['id'];
            $amount = (float) $ledger['amount'];
            $penalty = $amount * 0.03;
            $paid_at = $ledger['date_paid'];
            $is_past = Carbon::now()->startOfDay()->gt($ledger['due_date']);

            if( $is_past && !CashTermPenalty::where(['cash_term_ledger_id' => $cash_term_ledger_id, 'system_generated' => 1 ])->exists() && $paid_at == null ) {
                CashTermPenalty::create([
                    'reservation_number' => $ledger['reservation_number'],
                    'cash_term_ledger_id' => $cash_term_ledger_id,
                    'number' => $ledger['number'],
                    'penalty_amount' => $penalty,
                    'type' => 'amortization_penalty',
                    'system_generated' => 1,
                    'is_old' => 0
                ]);

                RealestateActivityLog::create([
                    'reservation_number' => $ledger['reservation_number'],
                    'action' => 'upload_file',
                    'description' => 'System Generated 3% penalty in Split ' . $ledger['number'] . ' with amount of ' . $penalty,
                    'model' => 'App\Models\Booking\Reservation',
                    'properties' => null,
                    'created_by' => $reservation['request_user_id'],
                ]);
            }
            $data[] = $ledger;
        }
        
        return $data;
    }

    public function reset_cash_term_ledger($reservation_number)
    {
        CashTermLedger::where('reservation_number', $reservation_number)
            ->update([
                'transaction_id' => NULL, 
                'date_paid' => NULL, 
                'paid_status' => NULL,
                'amount_paid' => NULL, 
                'pr_number' => NULL,
                'or_number' => NULL,
                'payment_type' => NULL,
                'payment_gateway' => NULL,
                'payment_gateway_reference_number' => NULL,
                'bank' => NULL,
                'bank_account_number' => NULL,
                'check_number' => NULL,
                'remarks' => NULL,
            ]);
    }

}