<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\ReservationCoBuyer;
use App\Models\RealEstate\LotInventory;
use App\Models\RealEstate\Client;
use App\Models\RealEstate\ReservationPromo;

use App\Models\RealEstate\SalesTeam;

use App\Models\RealEstate\AmortizationSchedule;
use App\Models\RealEstate\CashTermLedger;

use Carbon\Carbon;

class NewReservation extends Controller
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
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.Create.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $record_type = $request->record_save_type;

        $lot = LotInventory::where('subdivision', $request->subdivision)
                            ->where('block', $request->block)
                            ->where('lot', $request->lot)
                            ->first();
    
        if ($lot->status != 'available') {
            return response()->json(['message' => 'Lot is not available.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'client' => 'required',
            'subdivision' => 'required',
            'lot' => 'required',
            'block' => 'required',
            'area' => 'required',
            'price_per_sqm' => 'required'
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors();
            return response()->json(['message' => 'Validation failed', 'errors' => $errors], 400);
            
        }

        /**
         * Generate New Unique Reference Number
         */ 
        $reservation_number = "R-".\Str::upper(\Str::random(12));

        // Creates a new reference number if it encounters duplicate
        while (Reservation::where('reservation_number', $reservation_number)->exists()) {
            $reservation_number = "R-".\Str::upper(\Str::random(12));
        }

        $client = Client::where('id', $request->client)->with('agent.agent_details.team_member_of.team')->first();

        if (!$client->agent || !$client->agent->sales_id) {
            return response()->json(['message' => 'The client does not have agent assigned to them. Please complete the CRF and BIS.'], 400);
        }

        // Get agent manager
        // $sales_sub_team = SalesTeam::whereHas('members', function ($query) use ($client) {
        //                     $query->where('role', 'member');
        //                     $query->where('user_id', $client->agent->sales_id);
        //                 })->whereNotNull('parent_id')
        //                 ->first();
        // Get sales director
        // $sales_team = SalesTeam::whereHas('members', function ($query) use ($client) {
        //                     $query->where('role', 'member');
        //                     $query->where('user_id', $client->agent->sales_id);
        //                 })->whereNull('parent_id')
        //                 ->first();

        $agent = \App\User::where('id', $client->agent->sales_id)
                        ->parentTeam()
                        ->subTeam()
                        ->first()
                        ->toArray();
        

        if (!$agent) return response()->json(['message' => 'No agent found.'], 400);

        $interest_rate = getenv('FACTOR_PERCENTAGE') ? (float) env('FACTOR_PERCENTAGE') : 7;
        
        $new_reservation = Reservation::create([
            'client_id' => $request->client,
            'agent_id' => $client->agent->sales_id,
            
            'sales_manager_id' => (isset($agent['sub_team']['team']['owner_id'])) ? $agent['sub_team']['team']['owner_id'] : null,
            'sales_director_id' => (isset($agent['parent_team']['team']['owner_id'])) ? $agent['parent_team']['team']['owner_id'] : null,

            'referrer_id' => $request->referrer,
            'referrer_property' => $request->referrer_property,

            'status' => ($record_type != 'default') ? $record_type : 'pending',
            'client_number' => '',
            'reservation_number' => $reservation_number,
            'remarks' => $request->remarks,
            // 'promo_type' => $request->promo_type,

            'source' => $request->source,
            'interest_rate' => $interest_rate,

            'property_type' => $request->property_type,
            'subdivision' => $request->subdivision,
            'block' => $request->block,
            'lot' => $request->lot,
            'type' => $request->lot_type,
            'area' => $request->area,
            'price_per_sqm' => $request->price_per_sqm,
            'total_selling_price' => $request->area * $request->price_per_sqm,

            'reservation_fee_date' => $request->reservation_fee_date ? Carbon::parse($request->reservation_fee_date)->setTimezone('Asia/Manila') : null,
            'reservation_fee_amount' => $request->reservation_fee_amount,
            'payment_terms_type' => $request->payment_terms_type,

            'discount_amount' => $request->discount_amount,
            'with_twelve_percent_vat' => $request->with_twelve_percent_vat,

            'with_five_percent_retention_fee' => $request->with_retention_fee,

            // 
            'split_cash' => ( !is_null($request->cash_split_number) && $request->cash_split_number > 0 ) ? $request->split_cash : 0,
            'number_of_cash_splits' => $request->split_cash && !is_null($request->cash_split_number) ? $request->cash_split_number : 0,

            'split_cash_start_date' => ($request->split_cash && $request->split_cash_start_date) ? Carbon::parse($request->split_cash_start_date)->setTimezone('Asia/Manila') : null,
            'split_cash_end_date' => ($request->split_cash && $request->split_cash_end_date) ? Carbon::parse($request->split_cash_end_date)->setTimezone('Asia/Manila') : null,

            'downpayment_amount' => $request->downpayment_amount,
            'downpayment_due_date' => $request->downpayment_due_date ? Carbon::parse($request->downpayment_due_date)->setTimezone('Asia/Manila') : null,
            'number_of_years' => $request->number_of_years,
            'factor_rate' => $request->factor_rate,
            'monthly_amortization_due_date' => $request->monthly_amortization_due_date ? Carbon::parse($request->monthly_amortization_due_date)->setTimezone('Asia/Manila') : null,
            
            // 
            'split_downpayment' => (!is_null($request->downpayment_split_number) && $request->downpayment_split_number > 0) ? $request->split_downpayment : 0,
            'number_of_downpayment_splits' => ($request->split_downpayment && !is_null($request->downpayment_split_number)) ? $request->downpayment_split_number : 0,
            
            'split_downpayment_start_date' => ($request->split_downpayment && $request->split_downpayment_start_date) ? Carbon::parse($request->split_downpayment_start_date)->setTimezone('Asia/Manila') : null,
            'split_downpayment_end_date' => ($request->split_downpayment && $request->split_downpayment_end_date) ? Carbon::parse($request->split_downpayment_end_date)->setTimezone('Asia/Manila') : null,
            'old_reservation' => 0,

            // 'reservation_date' => $request->reservation_date,
        ]);

        /**
         * Save co-buyers
         */

        $co_buyers = [];

        if ($request->co_buyers) {
            foreach ($request->co_buyers as $co_buyer) {
                if ($co_buyer != $request->client) {
                    $co_buyers[] = [
                        'client_id' => $co_buyer,
                        'reservation_id' => $new_reservation['id'],
                    ];
                }
            }
        }

        ReservationCoBuyer::insert($co_buyers);

        /**
         * Create amortization schedule if payment terms type is 'IN-HOUSE ASSISTED FINANCING'
         */
        if ($request->payment_terms_type == 'in_house' && $request->number_of_years > 0 && $record_type == 'default') {

            // 
            $r = Reservation::where('reservation_number', $reservation_number)->first();
            
            // Loop record
            $amortization_to_record = [];;

            $initial_date = $request->split_downpayment ? Carbon::parse($request->split_downpayment_end_date)->setTimezone('Asia/Manila') : Carbon::parse($request->downpayment_due_date)->setTimezone('Asia/Manila');
            $amortization_date = Carbon::parse($request->monthly_amortization_due_date)->setTimezone('Asia/Manila');
            $amortization_date->hour = 0;
            $amortization_date->minute = 0;
            $amortization_date->second = 0;

            $interest = ($r->total_balance_in_house * ($interest_rate / 100)) / 12;
            $principal = $r->monthly_amortization - $interest;
            $balance = $r->total_balance_in_house - $principal;

            for ($i = 1; $i <= ($request->number_of_years * 12); $i++) {

                if ($initial_date->day > $amortization_date->day && $amortization_date->month != 2) {
                    if ($initial_date->day == 31 && in_array($amortization_date->month, [2, 4, 6, 9, 11])) {
                        $amortization_date->setDay(30);
                    } else {
                        $amortization_date->setDay($initial_date->day);
                    }
                }

                $amortization_to_record[$i] = [
                    'reservation_number' => $reservation_number,
                    'number' => $i,
                    'due_date' => $amortization_date,
                    'amount' => $new_reservation['monthly_amortization'],
                    'date_paid' => null,
                    'amount_paid' => null,
                    'pr_number' => null,
                    'or_number' => null,
                    'account_number' => null,
                    'generated_principal' => $principal,
                    'principal' => 0,
                    'generated_interest' => $interest,
                    'interest' => 0,
                    'generated_balance' => $balance,
                    'balance' => 0,
                    'remarks' => null,
                    'is_old' => 0,
                    'is_sales' => 1,
                    'is_collection' => 1,
                    'excess_payment' => 0,
                    'datetime' => null,
                    'created_at' => Carbon::now()->setTimezone('Asia/Manila')
                ];

                $interest = ($balance * ($interest_rate / 100)) / 12;
                $principal = $r->monthly_amortization - $interest;
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

            AmortizationSchedule::insert($amortization_to_record);

        }

        /**
         * Create cash ledger if payment terms type is 'CASH'
         */
        if( $record_type == 'default' ) {
            $this->cash_term_ledger($request, $reservation_number, $new_reservation);
        }

        /**
         * Save promos
         */
        if ($request->promos) {
            foreach ($request->promos as $promo) {
                ReservationPromo::create([
                    'reservation_number' => $reservation_number,
                    'promo_type' => $promo,
                ]);
            }
        }


        $status2 = "";

        if ($request->payment_terms_type == 'cash') {
            $status2 = "Cash";
        } else if ($request->payment_terms_type == 'in_house') {
            $status2 = "Current";
        } else {
            $status2 = "";
        }



        /**
         * Update inventory status if default type reservation
         */

        if($record_type == 'default') {
            LotInventory::where('subdivision', $request->subdivision)
                ->where('block', $request->block)
                ->where('lot', $request->lot)
                ->update([
                    'status' => 'reserved',
                    'status2' => $status2,
                    'price_per_sqm' => $request->price_per_sqm,
                ]);
        } else {
            LotInventory::where('subdivision', $request->subdivision)
                ->where('block', $request->block)
                ->where('lot', $request->lot)
                ->update([
                    'price_per_sqm' => $request->price_per_sqm,
                ]);
        }

        return response()->json(['reservation_number' => $reservation_number], 200);
    }

    public function cash_term_ledger($request, $reservation_number, $new_reservation)
    {
        /**
         * Create cash ledger if payment terms type is 'CASH'
         */

        if ($request->payment_terms_type == 'cash') {
            
            $reservation = Reservation::where('reservation_number', $reservation_number)->first();

            $splits = ($request->split_cash) ? $request->cash_split_number : 1;

            $initial_date = $request->split_cash ? Carbon::parse($request->split_cash_end_date)->setTimezone('Asia/Manila') : Carbon::parse($request->reservation_fee_date)->addMonth()->setTimezone('Asia/Manila');
            $cash_term_date = $request->split_cash ? Carbon::parse($request->split_cash_start_date)->setTimezone('Asia/Manila') : Carbon::parse($request->reservation_fee_date)->addMonth()->setTimezone('Asia/Manila');
            $cash_term_date->hour = 0;
            $cash_term_date->minute = 0;
            $cash_term_date->second = 0;
            $cash_term_data = [];
            
            for( $i = 1; $i <= $splits; $i++ ) {

                if ($initial_date->day > $cash_term_date->day && $cash_term_date->month != 2) {
                    if ($initial_date->day == 31 && in_array($cash_term_date->month, [2, 4, 6, 9, 11])) {
                        $cash_term_date->setDay(30);
                    } else {
                        $cash_term_date->setDay($initial_date->day);
                    }
                }

                $cash_term_data[$i] = [
                    'reservation_number' => $reservation_number,
                    'number' => $i,
                    'due_date' => $cash_term_date,
                    'amount' => $request->split_cash ? $new_reservation['split_payment_amount'] : $new_reservation['total_amount_payable'],
                    'date_paid' => null,
                    'amount_paid' => null,
                    'remarks' => null,
                    'datetime' => null,
                    'created_at' => Carbon::now()->setTimezone('Asia/Manila')
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

            CashTermLedger::insert($cash_term_data);

        }
    }
}
