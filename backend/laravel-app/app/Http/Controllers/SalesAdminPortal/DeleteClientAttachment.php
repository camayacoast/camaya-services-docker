<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\ClientInformationAttachment;
use App\Models\RealEstate\ClientInformation;
use App\Models\RealEstate\Client;

use Carbon\Carbon;

class DeleteClientAttachment extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.DeleteAttachments.Client')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }
        // return $request->all();
        $attachment = ClientInformationAttachment::find($request->id);

        if (!$attachment) {
            return response()->json([], 400);
        }

        ClientInformationAttachment::where('id', $request->id)->update([
            'deleted_at' => Carbon::now(),
            'deleted_by' => $request->user()->id,
        ]);

        $client_info = ClientInformation::where('id', $attachment->client_information_id)->first();

        return Client::where('id', $client_info->user_id)->with(['information.attachments' => function ($q) {
                                                        $q->whereNull('deleted_at');
                                                    }])->with('spouse')->with('agent.agent_details')->first();
    }
}
