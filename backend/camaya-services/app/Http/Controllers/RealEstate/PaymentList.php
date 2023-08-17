<?php

namespace App\Http\Controllers\RealEstate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\RealEstatePayment;
use App\Http\Requests\RealEstate\PaymentListRequest;
use Carbon\Carbon;

use Illuminate\Support\Facades\Http;

class PaymentList extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(PaymentListRequest $request)
    {
        //
        // return $request->all();
        $realestatePayments = new RealEstatePayment;
        $realestatePayments->user = $request->user();

        return $realestatePayments::with(['paymentStatuses' => function ($query) {
                $query->orderBy('created_at', 'DESC');
            }])
            ->with('reservation')
            ->with('verifiedBy')
            ->orderBy('id', 'DESC')
            ->get();     
    }
}
