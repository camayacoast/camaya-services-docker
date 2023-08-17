<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\RealEstate\AmortizationSchedule;
use App\Models\RealEstate\RealEstatePayment;
use App\Models\RealEstate\CashTermLedger;

use Carbon\Carbon;

class GetDashboardData extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Dashboard')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }
        //
        // $cancelled_reservations = \App\Models\RealEstate\Reservation::select('status')->where('status', 'cancelled')->count();
        $pending_reservations = \App\Models\RealEstate\Reservation::select('status')->where('status', 'pending')->count();
        $reviewed_reservations = \App\Models\RealEstate\Reservation::select('status')->where('status', 'reviewed')->count();
        $approved_reservations = \App\Models\RealEstate\Reservation::select('status')->where('status', 'approved')->count();
        $draft_reservations = \App\Models\RealEstate\Reservation::select('status')->where('status', 'draft')->count();


        $clients_count = \App\Models\RealEstate\Client::count();
        $clients_for_review_count = \App\Models\RealEstate\Client::whereHas('information', function ($q) {
            $q->where('status', 'for_review');
        })->count();

        return [
            'reservations' => [
                'pending' => $pending_reservations,
                'reviewed' => $reviewed_reservations,
                'approved' => $approved_reservations,
                'draft' => $draft_reservations,
            ],
            'clients' => [
                'total' => $clients_count,
                'for_review' => $clients_for_review_count,
            ]
        ];
    }

    public function receivables(Request $request)
    {
        $term = $request->term;
        $year = $request->year;
        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $monthly_paid_graph = [0,0,0,0,0,0,0,0,0,0,0,0];
        $monthly_unpaid_graph = [0,0,0,0,0,0,0,0,0,0,0,0];
        $monthly_total_graph = [0,0,0,0,0,0,0,0,0,0,0,0];

        $allowed_statuses = ['approved'];
        $year_start_date = $year . '-01-01 00:00:00';
        $year_end_date = $year . '-12-31 23:59:59';

        if( $term == 'cash' ) {
            $model = new CashTermLedger;
            $table = 'cash_term_ledgers';
        } else {
            $model = new AmortizationSchedule;
            $table = 'amortization_schedules';
        }

        $total_amount_receivables = $model::join('reservations', 'reservations.reservation_number', '=', $table.'.reservation_number')
            ->whereIn('reservations.status', $allowed_statuses)
            ->where($table . '.due_date', '>=', $year_start_date)
            ->where($table . '.due_date', '<=', $year_end_date)
            ->sum($table . '.amount');

        $total_amount_receivables_paid = $model::join('reservations', 'reservations.reservation_number', '=', $table.'.reservation_number')
            ->whereIn('reservations.status', $allowed_statuses)
            ->whereNotNull($table.'.amount_paid')
            ->where($table . '.due_date', '>=', $year_start_date)
            ->where($table . '.due_date', '<=', $year_end_date)
            ->sum($table . '.amount');

        $total_amount_receivables_unpaid = $model::join('reservations', 'reservations.reservation_number', '=', $table.'.reservation_number')
            ->whereIn('reservations.status', $allowed_statuses)
            ->whereNull($table.'.amount_paid')
            ->where($table . '.due_date', '>=', $year_start_date)
            ->where($table . '.due_date', '<=', $year_end_date)
            ->sum($table . '.amount');

        // Paid
        $monthly_paid_receivable = $model::select(DB::raw('DATE_FORMAT(date_paid, "%c") as month'), 'date_paid', 'amount_paid')
            ->join('reservations', 'reservations.reservation_number', '=', $table.'.reservation_number')
            ->whereIn('reservations.status', $allowed_statuses)
            ->where($table . '.date_paid', '>=', $year_start_date)
            ->where($table . '.date_paid', '<=', $year_end_date)
            ->whereNotNull($table.'.amount_paid')
            ->get();

        if( $monthly_paid_receivable ) {
            $paid_grouped = $monthly_paid_receivable->groupBy('month');
            $monthly_paid_collection = $paid_grouped->all();
            
            foreach( $monthly_paid_collection as $month => $record ) {
                $monthly_amount_paid = collect($record)->sum('amount_paid');
                $monthly_paid_graph[$month - 1] = $monthly_amount_paid;
            }
        }
        
        // Unpaid
        $monthly_unpaid_receivable = $model::select(DB::raw('DATE_FORMAT(due_date, "%c") as month'), 'due_date', 'amount')
            ->join('reservations', 'reservations.reservation_number', '=', $table.'.reservation_number')
            ->whereIn('reservations.status', $allowed_statuses)
            ->where($table . '.due_date', '>=', $year_start_date)
            ->where($table . '.due_date', '<=', $year_end_date)
            ->whereNull($table.'.amount_paid')
            ->get();

        if( $monthly_unpaid_receivable ) {
            $unpaid_grouped = $monthly_unpaid_receivable->groupBy('month');
            $monthly_unpaid_collection = $unpaid_grouped->all();
            
            foreach( $monthly_unpaid_collection as $month => $record ) {
                $monthly_amount_unpaid = collect($record)->sum('amount');
                $monthly_unpaid_graph[$month - 1] = $monthly_amount_unpaid;
            }
        } 

        // Monthly total graph
        $monthly_total_receivable = $model::select(DB::raw('DATE_FORMAT(due_date, "%c") as month'), 'due_date', 'amount')
            ->join('reservations', 'reservations.reservation_number', '=', $table.'.reservation_number')
            ->whereIn('reservations.status', $allowed_statuses)
            ->where($table . '.due_date', '>=', $year_start_date)
            ->where($table . '.due_date', '<=', $year_end_date)
            ->get();

        if( $monthly_total_receivable ) {
            $total_grouped = $monthly_total_receivable->groupBy('month');
            $monthly_total_collection = $total_grouped->all();
            
            foreach( $monthly_total_collection as $month => $record ) {
                $monthly_amount_total = collect($record)->sum('amount');
                $monthly_total_graph[$month - 1] = $monthly_amount_total;
            }
        } 

        return [
            'total_amount_receivables' => isset($total_amount_receivables) ? $total_amount_receivables : 0,
            'total_amount_receivables_paid' => isset($total_amount_receivables_paid) ? $total_amount_receivables_paid : 0,
            'total_amount_receivables_unpaid' => isset($total_amount_receivables_unpaid) ? $total_amount_receivables_unpaid : 0,
            'monthly_amount_receivables' => [
                'paid' => $monthly_paid_graph,
                'unpaid' => $monthly_unpaid_graph,
                'total' => $monthly_total_graph,
            ]
        ];

    }

    public function revenues(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $date = Carbon::now();

        $types = [
            'reservation_fee_payment' => 'Reservation',
            'downpayment' => 'Downpayment',
            'monthly_amortization_payment' => 'Amortization' ,
            'penalty' => 'Penalty',
            'split_cash' => 'Split Cash',
            'partial_cash' => 'Partial Cash',
            'full_cash' => 'Full Cash',
            'retention_fee' => 'Retention Fee',
            'title_fee' => 'Title Fee',
        ];

        $payment_types = [
            'reservation_fee_payment', 'downpayment', 'monthly_amortization_payment', 'penalty',
            'split_cash', 'partial_cash', 'full_cash', 'retention_fee', 'title_fee'
        ];
        
        $output = [];
        $amounts = [];

        $month_start_date = $year . '-' . $month . '-01 00:00:00';
        $month_end_date = $year . '-' . $month . '-31 23:59:59';

        $payments = RealEstatePayment::where('is_verified', 1)
            ->where('paid_at', '>=', $month_start_date)
            ->where('paid_at', '<=', $month_end_date)
            ->whereIn('payment_type', $payment_types)
            ->get();

        if( $payments ) {

            $counter = 0;
            $payment_grouped = $payments->groupBy('payment_type');
            $payment_collection = $payment_grouped->all();
            
            foreach( $types as $key => $label ) {

                $has_amount = false;

                foreach( $payment_collection as $payment_type => $record ) {
                    if( $key == $payment_type ) {
                        $has_amount = true;
                        $payment_total_paid = collect($record)->sum('payment_amount');
                        $output[$counter]['key'] = $counter;
                        $output[$counter]['label'] = isset($types[$payment_type]) ? $types[$payment_type] : '';
                        $output[$counter]['amount'] = number_format(round($payment_total_paid,2), 2);
                    }
                }

                if( !$has_amount ) {
                    $payment_total_paid = 0;
                    $output[$counter]['key'] = $counter;
                    $output[$counter]['label'] = $label;
                    $output[$counter]['amount'] = number_format(round($payment_total_paid,2), 2);
                }

                $amounts[] = round($payment_total_paid,2);
                $counter = $counter + 1; 
            }
            
        }

        $output[count($types)]['key'] = count($types);
        $output[count($types)]['label'] = 'Total';
        $output[count($types)]['amount'] = number_format(round(array_sum($amounts),2), 2);

        return $output;
    }
}
