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

use App\Http\Controllers\SalesAdminPortal\NewReservation;

use Carbon\Carbon;

class Reservations extends Controller
{

    public function updateReservation(Request $request)
    {
        $output = false;

        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.Create.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

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

        if( $request->record_save_type == 'draft' ) {
            $this->updateDraftRecord($request);
        } else {
            $this->drafttoPendingRecord($request);
        }
        
    }

    public function updateDraftRecord($request)
    {
        $reservation_number = $request->reservation_number;
        $payment_terms_type = $request->payment_terms_type;

        $client = Client::where('id', $request->client)->with('agent.agent_details.team_member_of.team')->first();
        if (!$client->agent || !$client->agent->sales_id) {
            return response()->json(['message' => 'The client does not have agent assigned to them. Please complete the CRF and BIS.'], 400);
        }

        $agent = \App\User::where('id', $client->agent->sales_id)
                ->parentTeam()
                ->subTeam()
                ->first()
                ->toArray();

        if (!$agent) {
            return response()->json(['message' => 'No agent found.'], 400);
        }

        $interest_rate = $request->factor_percentage;

        $reservation = Reservation::where('reservation_number', $reservation_number)->first();

        $reservation->update([
            'client_id' => $request->client,
            'agent_id' => $client->agent->sales_id,
            
            'sales_manager_id' => (isset($agent['sub_team']['team']['owner_id'])) ? $agent['sub_team']['team']['owner_id'] : null,
            'sales_director_id' => (isset($agent['parent_team']['team']['owner_id'])) ? $agent['parent_team']['team']['owner_id'] : null,

            'referrer_id' => $request->referrer,
            'referrer_property' => $request->referrer_property,

            'status' => ($request->record_save_type == 'draft') ? 'draft' : 'pending',
            'client_number' => '',
            'remarks' => $request->remarks,

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
            'split_downpayment' => ($request->downpayment_split_number > 0 || !is_null($request->downpayment_split_number) ) ? $request->split_downpayment : 0,
            'number_of_downpayment_splits' => $request->split_downpayment && !is_null($request->downpayment_split_number) ? $request->downpayment_split_number : 0,
            
            'split_downpayment_start_date' => ($request->split_downpayment && $request->split_downpayment_start_date) ? Carbon::parse($request->split_downpayment_start_date)->setTimezone('Asia/Manila') : null,
            'split_downpayment_end_date' => ($request->split_downpayment && $request->split_downpayment_end_date) ? Carbon::parse($request->split_downpayment_end_date)->setTimezone('Asia/Manila') : null,
            'old_reservation' => 0,
        ]);


        // Updating co buyers list
        $co_buyers = [];

        // Remove if not in the list
        $delete_co_buyer = ReservationCoBuyer::whereNotIn('client_id', $request->co_buyers)
            ->where('reservation_id', $reservation->id)->delete();

        if ($request->co_buyers) {
            foreach ($request->co_buyers as $co_buyer) {
                if ($co_buyer != $request->client) {

                    $exists = ReservationCoBuyer::where('client_id', $co_buyer)
                        ->where('reservation_id', $reservation->id)->exists();

                    if( !$exists ) {
                        $co_buyers[] = [
                            'client_id' => $co_buyer,
                            'reservation_id' => $reservation->id,
                        ];
                    }
                    
                }
            }
        }
        ReservationCoBuyer::insert($co_buyers);

        // Update promo list
        if ($request->promos) {

            $delete_reservation_promos = ReservationPromo::whereNotIn('promo_type', $request->promos)
                ->where('reservation_number', $reservation_number)->delete();

            foreach ($request->promos as $promo) {

                $exists = ReservationPromo::where('promo_type', $promo)
                        ->where('reservation_number', $reservation_number)->exists();

                if( !$exists ) {
                    ReservationPromo::create([
                        'reservation_number' => $reservation_number,
                        'promo_type' => $promo,
                    ]);
                }
            }
        }

        LotInventory::where('subdivision', $request->subdivision)
            ->where('block', $request->block)
            ->where('lot', $request->lot)
            ->update([
                'price_per_sqm' => $request->price_per_sqm,
            ]);

        return $reservation;

    }

    public function drafttoPendingRecord($request)
    {
        // dd($request);
        $reservation_number = $request->reservation_number;
        $payment_terms_type = $request->payment_terms_type;

        $interest_rate = $request->factor_percentage;

        // Update Reservation Details
        $reservation = $this->updateDraftRecord($request);

        if ($request->payment_terms_type == 'in_house' && $request->number_of_years > 0) {

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
                    'amount' => $r->monthly_amortization,
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

        $newReservation = new NewReservation;
        $newReservation->cash_term_ledger($request, $reservation_number, $reservation);

        $status2 = "";

        if ($request->payment_terms_type == 'cash') {
            $status2 = "Cash";
        } else if ($request->payment_terms_type == 'in_house') {
            $status2 = "Current";
        } else {
            $status2 = "";
        }

        LotInventory::where('subdivision', $request->subdivision)
            ->where('block', $request->block)
            ->where('lot', $request->lot)
            ->update([
                'status' => 'reserved',
                'status2' => $status2,
                'price_per_sqm' => $request->price_per_sqm,
            ]);

        return response()->json(['reservation_number' => $reservation_number], 200);
    }

    public function deleteReservation(Request $request)
    {

        try {

            $reservation_number = $request->reservation_number;

            $reservation = Reservation::where('reservation_number', $reservation_number)
                ->where('status', 'draft')
                ->first();

            ReservationCoBuyer::where('reservation_id', $reservation->id)->delete();
            ReservationPromo::where('reservation_number', $reservation_number)->delete();
            $reservation->delete();

            return response()->json(['reservation_number' => $reservation_number], 200);

        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

}
