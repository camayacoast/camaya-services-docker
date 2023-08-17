<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UploadFileAttachment extends Controller
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

        $path = $request->file('file')->store(
            '/attachments/client_information', 'public'
        );

        // $client_info = ClientInformation::where('id', $request->client_information_id)->first();

        // $path = Storage::disk('public')->putFileAs('/attachments/client_information/'.$client_info->user_id, $request->file('file'), Str::random(40) . '.' . $request->file->getClientOriginalExtension());

        return [
            'file_path' => $path,
            'file_name' => $request->file('file')->getClientOriginalName(),
            'content_type' => $request->file('file')->getMimeType(),
            'file_size' => $request->file('file')->getSize(),
            'attachment_type' => $request->attachment_type,
        ];
    }
}
