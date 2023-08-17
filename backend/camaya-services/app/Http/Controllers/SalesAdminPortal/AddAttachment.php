<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\ClientInformationAttachment;
use App\Models\RealEstate\ClientInformation;
use App\Models\RealEstate\Client;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

use Str;

class AddAttachment extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.UploadAttachments.Client')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $client_info = ClientInformation::where('id', $request->client_information_id)->first();

        foreach( $request->file as $key => $upload ) {

            $path = Storage::disk('public')->putFileAs('/attachments/client_information/'.$client_info->user_id, $request->file('file')[$key], Str::random(40) . '.' . $upload->getClientOriginalExtension());
            
            ClientInformationAttachment::create([
                'client_information_id' => $request->client_information_id,
                'type' => '',
                'file_name' => $request->file('file')[$key]->getClientOriginalName(),
                'content_type' => $request->file('file')[$key]->getMimeType(),
                'file_size' => $request->file('file')[$key]->getSize(),
                'file_path' => env('APP_URL').Storage::url($path),
                'created_by' => $request->user()->id,
                'deleted_by' => null,
            ]);
        }

        $client = Client::where('id', $client_info->user_id)
            ->with(['information.attachments' => function ($q) {
                $q->whereNull('deleted_at');
            }])
            ->with('spouse')
            ->with('agent.agent_details')
            ->first();
        
        return $client;
    }
}
