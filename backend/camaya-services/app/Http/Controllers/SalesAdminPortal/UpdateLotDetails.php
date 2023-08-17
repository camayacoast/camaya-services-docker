<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\LotInventory;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Imports\SalesAdminPortal\Inventory;
use App\Models\RealEstate\Reservation;

class UpdateLotDetails extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.Edit.Lot')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $inventoryImports = new Inventory;
        $statusOne = $inventoryImports->statusOne;
        $statusTwo = $inventoryImports->statusTwo;

        // return $request->all();

        $validator = Validator::make($request->all(), [
                'phase' => 'required',
                'subdivision' => 'required',
                'lot' => 'required|numeric',
                'block' => 'required',
                'area' => 'required|numeric',
                'status' => 'required|in:' . implode(',', $statusOne),
                'status2' => 'required|in:' . implode(',', $statusTwo),
            ],
            [
                'status2.in' => 'Status2 value is not listed in allowed statuses. ' . '(' . implode(', ', $statusTwo) . ')'
            ]
        );

        if ($validator->fails()) {

            $errors = $validator->errors();
            return response()->json(['message' => $errors->first(), 'errors' => $errors], 400);
            
        }

        // Check if lot details is not duplicate
        $checkIfDuplicate = LotInventory::where('subdivision', $request->subdivision)
                            ->where('lot', $request->lot)
                            ->where('block', $request->block)
                            ->where('id', '!=', $request->id)
                            ->first();

        if ($checkIfDuplicate) {
            return response()->json(['message' => 'Duplicate record.'], 400);
        }

        // Update the record

        $old = LotInventory::where('id', $request->id)->first();

        $reservation = Reservation::where('subdivision', $old->subdivision)
                ->where('block', $old->block)
                ->where('lot', $old->lot)
                ->where('type', $old->type)
                ->where('area', $old->area)
                ->whereNotIn('status', ['cancelled', 'void'])->first();
        
        if( $reservation ) {
            $reservation->update([
                'subdivision' => $request->subdivision,
                'block' => $request->block,
                'lot' => $request->lot,
                'type' => $request->type,
                'area' => $request->area,
            ]);
        }

        $updateRecord = LotInventory::where('id', $request->id)
            ->update([
                'subdivision' => $request->subdivision,
                'phase' => $request->phase,
                'lot' => $request->lot,
                'block' => $request->block,
                'area' => $request->area,
                'type' => $request->type,
                'price_per_sqm' => $request->price_per_sqm,
                'status' => $request->status,
                'status2' => $request->status2,
                'remarks' => $request->remarks,
                'color' => $request->color,
            ]);

        Log::debug('Updated lot inventory ('. $request->id .') | Old price: '. $old['price_per_sqm']. ', New price: '. $request->price_per_sqm.' | Old status: '. $old['status'].', New status: '. $request->status.' | By '. $request->user()->first_name .' '. $request->user()->last_name.' ('.$request->user()->id.')');

        return LotInventory::where('id', $request->id)->first();
    }

    public function delete_lot(Request $request)
    {
        $delete = LotInventory::where('id', $request->id)->delete();
    }
}
