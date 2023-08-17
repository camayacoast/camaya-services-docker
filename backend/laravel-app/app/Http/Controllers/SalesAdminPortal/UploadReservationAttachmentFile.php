<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\ReservationAttachment;
use App\Models\RealEstate\ClientInformation;
use App\Models\RealEstate\ClientInformationAttachment;

use Illuminate\Support\Facades\Storage;

use Str;

class UploadReservationAttachmentFile extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.UploadAttachments.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }
        
        // $path = $request->file('file')->store(
        //     '/attachments/reservation', 'public'
        // );

        $path = Storage::disk('public')->putFileAs('/attachments/reservation/'.$request->reservation_number, $request->file('file'), Str::random(40) . '.' . $request->file->getClientOriginalExtension());

        $new_attachment = ReservationAttachment::create([
            'reservation_number' => $request->reservation_number,
            // 'type' => $request->attachment_type,
            'file_name' => $request->file('file')->getClientOriginalName(),
            'content_type' => $request->file('file')->getMimeType(),
            'file_size' => $request->file('file')->getSize(),
            'file_path' => env('APP_URL').Storage::url($path),
            'created_by' => $request->user()->id,
            'deleted_by' => null,
        ]);


        return $new_attachment;
    }

    public function approve_attachments(Request $request)
    {
        $id_to_update = [];
        $no_attachment_type = [];
        $user_id = $request->client_number;

        $client = ClientInformation::where('user_id', $user_id)
            ->with('attachments')
            ->first();

        foreach( $client->attachments as $key => $attachment ) {
            if( $attachment->type !== '' && !is_null($attachment->type) ) {
                $id_to_update[] = $attachment->id;
            }
            
            if( $attachment->type == '' ) {
                $no_attachment_type[] = $attachment->id;
            }
        }

        $update = ClientInformationAttachment::whereIn('id', $id_to_update)->where('status', '!=', 'approved')->update([
            'status' => 'approved'
        ]);

        if( $update ) {
            echo json_encode(['message' => 'Attachments status updated.']);
        } else {
            if( !empty($no_attachment_type) ) {
                echo json_encode(['message' => 'Please select attachment type.']);
            } else {
                echo json_encode(['message' => 'Nothing to update.']);
            }
            
        }
        
    }
}
