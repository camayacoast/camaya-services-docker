<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\LotInventory;

use Illuminate\Support\Facades\Validator;

class NewLotRecord extends Controller
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

        if ( 
            $request->user()->user_type != 'admin' ||
            !$request->user()->hasPermissionTo('SalesAdminPortal.Create.Lot')
        ) {
            return response()->json(['message' => 'Unauthorized.'], 400);
        }

        if( isset($request->total_selling_price) ) {
            $rules = [
                'subdivision' => 'required',
                'lot' => 'required',
                'block' => 'required',
                'area' => 'required',
                'total_selling_price' => 'required',
            ];
            $price_per_sqm = round($request->total_selling_price / $request->area, 2);
        } else {
            $rules = [
                'subdivision' => 'required',
                'lot' => 'required',
                'block' => 'required',
                'area' => 'required',
                'price_per_sqm' => 'required',
            ];
            $price_per_sqm = $request->price_per_sqm;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            $errors = $validator->errors();
            return response()->json(['message' => 'Validation failed', 'errors' => $errors], 400);
            
        }

        // Check if lot details is not duplicate
        $checkIfDuplicate = LotInventory::where('subdivision', $request->subdivision)
                            ->where('lot', $request->lot)
                            ->where('block', $request->block)
                            // ->where('id', '!=', $request->id)
                            ->first();

        if ($checkIfDuplicate) {
            return response()->json(['message' => 'Duplicate record.'], 400);
        }

        // Update the record

        $subdivision = explode('++', $request->subdivision);

        $newRecord = LotInventory::create([
            'subdivision' => $subdivision[0],
            'subdivision_name' => isset($subdivision[1]) ? $subdivision[1] : '',
            'phase' => $request->phase,
            'lot' => $request->lot,
            'block' => $request->block,
            'area' => $request->area,
            'type' => $request->type,
            'price_per_sqm' => $price_per_sqm,
            'status' => $request->status,
            'property_type' => $request->property_type,
        ]);

        return $newRecord;
    }
}
