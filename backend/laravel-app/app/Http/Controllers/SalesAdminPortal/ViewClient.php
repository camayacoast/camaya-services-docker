<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\Client;

class ViewClient extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Client')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $client = Client::where('id', $request->id)->first();

        if (!$client) {
            return response()->json(['error' => 'CLIENT_NOT_FOUND', 'message' => 'Client not found.'], 404);
        }

        return $client->load(['information.attachments' => function ($q) {
            $q->whereNull('deleted_at');
        }])->load('spouse')->load('agent');
    }
}
