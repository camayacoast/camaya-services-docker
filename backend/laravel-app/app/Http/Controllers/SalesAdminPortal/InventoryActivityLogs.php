<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RealEstate\InventoryActivityLog;

class InventoryActivityLogs extends Controller
{
    public function show(Request $request)
    {
        return InventoryActivityLog::where('type', $request->type)
            ->orderBy('created_at', 'DESC')
            ->with('causer')
            ->get();
    }

    public function store(Request $request) {

        InventoryActivityLog::create([
            'type' => $request->type,
            'action' => $request->action,
            'description' => $request->description,
            'model' => 'App\Models\SalesAdminPortal\InventoryActivityLog',
            'properties' => null,
            'details'=> json_encode($request->details),
            'created_by' => $request->user()->id,
        ]);

    }
}