<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;

class RemoveFileAttachment extends Controller
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

        $fileToDelete = basename($request->file_path);

        $fileDeleted = Storage::delete("public/attachments/client_information/$fileToDelete");

        if ($fileDeleted) {
            return response()->json(['message' => 'File removed!'], 200);
        }

        return response()->json(['error' => 'File not deleted'], 500);
    }
}
