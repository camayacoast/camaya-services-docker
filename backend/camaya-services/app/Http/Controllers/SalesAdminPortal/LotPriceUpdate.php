<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\LotInventory;

class LotPriceUpdate extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.UpdatePrices.Lot')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        // return LotInventory::where('subdivision', $request->subdivision)->get();

        // Get lowest price per sqm
        // $min = LotInventory::where('subdivision', $request->subdivision)->min('price_per_sqm');

        // // Get highest price per sqm
        // $max = LotInventory::where('subdivision', $request->subdivision)->max('price_per_sqm');

        // // Increase
        // if ($request->operation == 'increase') {
        //     LotInventory::where('subdivision', $request->subdivision)->increment('price_per_sqm', $request->amount);
        // } else if (($request->amount < $min) && $request->operation == 'decrease') {
        //     // Decrease
        //     LotInventory::where('subdivision', $request->subdivision)->decrement('price_per_sqm', $request->amount);
        // } else {
        //     return response()->json(['message' => "Failed to update price per sqm!"], 400);
        // }

        if ( $request->user()->user_type != 'admin' && !$request->user()->hasRole(['Sales Admin']) ) {
            return response()->json(['message' => 'Unauthorized.'], 400);
        }

        if (!$request->subdivision || $request->price_per_sqm <= 0) {
            return response()->json(['message' => "Failed to update price per sqm!"], 400);
        }

        $phase = $request->phase == 'NO_PHASE' ? null : $request->phase;
        $type = $request->type == 'NO_TYPE' ? null : $request->type;
        $block = $request->block == 'NO_BLOCK' ? null : $request->block;
        $lot = $request->lot == 'NO_LOT' ? null : $request->lot;

        if( $request->property_type == 'condo' ) {
            $tsp = round($request->price_per_sqm / $request->area, 2);
        }

        $update = LotInventory::where('subdivision', $request->subdivision)
                                ->where('phase', $phase)
                                ->where('type', $type)
                                ->where('block', $block)
                                ->where('lot', $lot)
                                ->whereNotIn('status', ['reserved', 'sold', 'pending_migration'])
                                ->update([
                                    'price_per_sqm' => ($request->property_type == 'condo') ? $tsp : $request->price_per_sqm
                                ]);
        
        return 'OK';
    }
}
