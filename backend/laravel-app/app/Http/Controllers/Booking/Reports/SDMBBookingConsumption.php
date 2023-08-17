<?php

namespace App\Http\Controllers\Booking\Reports;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
// use App\Models\Booking\Customer;
use App\Models\RealEstate\SalesTeam;
use App\Models\RealEstate\SalesTeamMember;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SDMBBookingConsumption extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($start_date, $end_date, $download = false)
    {      

        $data = [];
        $sales_director_ids = [];

        if (!$start_date || !$end_date) {
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }  

        $sales_teams = SalesTeam::with(['owner.user'])->get();

        foreach($sales_teams as $k=>$sales_team) {

            if (isset($sales_team->owner->user->id)) {

                $data[] = [
                    'sd_id' => $sales_team->owner->user->id,
                    'sd_name' => $sales_team->owner->user->first_name . ' ' . $sales_team->owner->user->last_name,
                    'ferry_total_sales' => 0,
                    'land_total_sales' => 0,     
                ];    
                
                $sales_director_ids[] = $sales_team->owner->user->id;
            }
        }

        $bookings = \App\Models\Booking\Booking::whereIn('sales_director_id', $sales_director_ids)
                                                ->where('bookings.start_datetime', '>=', $start_date)
                                                ->where('bookings.start_datetime', '<=', $end_date)
                                                ->where('bookings.status', 'confirmed')
                                                ->select('reference_number', 'sales_director_id', 'mode_of_transportation')
                                                ->withCount(['invoices as invoices_grand_total' => function ($q) {
                                                    $q->select(\DB::raw('sum(grand_total)'));
                                                }])
                                                ->get();
            
        foreach($bookings as $k=>$booking) {

            $sd = collect($data)->firstWhere('sd_id', $booking['sales_director_id']);

            $index = collect($data)->search( function($item) use ($booking) {
                return $booking['sales_director_id'] == $item['sd_id'];
            });

            if ($booking['mode_of_transportation'] == 'camaya_transportation') $data[$index]['ferry_total_sales'] += $booking['invoices_grand_total'];
                
            if ($booking['mode_of_transportation'] != 'camaya_transportation') $data[$index]['land_total_sales'] += $booking['invoices_grand_total'];

        }

        if ($download) {
            return Excel::download(
                new ReportExport('reports.booking.sdmb-booking-consumption', array_values($data)),
                'report.xlsx'
            );
        }

        return response()->json([
            'status' => true,
            'data' => array_values($data),
            'ids' => $bookings
        ]);
    }
}
