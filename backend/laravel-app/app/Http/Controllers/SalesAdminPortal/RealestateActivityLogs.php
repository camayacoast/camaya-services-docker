<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RealEstate\RealestateActivityLog;
use App\Models\RealEstate\RealestatePaymentActivityLog;

class RealestateActivityLogs extends Controller
{
    public function show(Request $request)
    {
        return RealestateActivityLog::where('reservation_number', $request->reservation_number)
            ->orderBy('created_at', 'DESC')
            ->with('causer')
            ->get();
    }

    public function store(Request $request) {

        RealestateActivityLog::create([
            'reservation_number' => $request->reservation_number,
            'action' => $request->action,
            'description' => $request->description,
            'model' => 'App\Models\RealEstate\RealestatePaymentActivityLog',
            'properties' => null,
            'created_by' => $request->user()->id,
        ]);

    }

    public function paymentsActivityLogs()
    {
        return RealestatePaymentActivityLog::orderBy('created_at', 'DESC')
            ->with('causer')
            ->get();
    }
}