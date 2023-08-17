<?php

namespace App\Http\Controllers\Collections;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\RealEstate\AddFeesRequest;
use App\Models\RealEstate\AmortizationFee;

class AddFees extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(AddFeesRequest $request)
    {
        //
        // return $request->user()->id;

        $newAmortizationFee = AmortizationFee::create([
            'reservation_number' => $request->reservation_number,
            'type' => $request->type,
            'amount' => $request->amount,
            'remarks' => $request->remarks,
            'created_by' => $request->user()->id,
        ]);

        if (!$newAmortizationFee) {
            return response()->json(['error' => 'error'], 400);
        }

        return response()->json(['message' => 'OK'], 200);
    }
}
