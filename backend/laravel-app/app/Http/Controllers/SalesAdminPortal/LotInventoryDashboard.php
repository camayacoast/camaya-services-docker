<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RealEstate\LotInventory;

class LotInventoryDashboard extends Controller
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
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Dashboard')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $lot = LotInventory::select('subdivision', \DB::raw('count(*) as total'))
            ->selectRaw("count(distinct case when status = 'available' then id end) as available")
            ->selectRaw("count(distinct case when status = 'reserved' then id end) as reserved")
            ->selectRaw("count(distinct case when status = 'sold' then id end) as sold")
            ->where('property_type', 'lot')
            ->groupBy('subdivision')->get();

        $condo = LotInventory::select('subdivision', \DB::raw('count(*) as total'))
            ->selectRaw("count(distinct case when status = 'available' then id end) as available")
            ->selectRaw("count(distinct case when status = 'reserved' then id end) as reserved")
            ->selectRaw("count(distinct case when status = 'sold' then id end) as sold")
            ->where('property_type', 'condo')
            ->groupBy('subdivision')->get();

        $data = [
            'lot' => $lot,
            'condo' => $condo,
        ];

        return $data;
    }
}
