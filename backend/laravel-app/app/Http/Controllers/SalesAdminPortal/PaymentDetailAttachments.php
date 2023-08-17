<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\PaymentDetailAttachment;
use App\Models\RealEstate\RealestateActivityLog;

use Illuminate\Support\Facades\Storage;
// use Illuminate\Http\File;

class PaymentDetailAttachments extends Controller
{

    public function payment_attachments($transaction_id)
    {
        return ( $transaction_id !== false ) ? PaymentDetailAttachment::where('transaction_id', $transaction_id)->orderBy('id', 'desc')->get() : [];
    }

    public function upload(Request $request) 
    {

        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.UploadAttachments.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $path = $request->file('file')->store(
            '/attachments/payment_details/'.$request->transaction_id, 'public'
        );

        $newAttachment = PaymentDetailAttachment::create([
            'transaction_id' => $request->transaction_id,
            'type' => $request->file('file')->getMimeType(),
            'file_name' => $request->file('file')->getClientOriginalName(),
            'content_type' => $request->file('file')->getMimeType(),
            'file_size' => $request->file('file')->getSize(),
            'file_path' => env('APP_URL').Storage::url($path),
            'created_by' => $request->user()->first_name . ' ' . $request->user()->last_name,
            'deleted_by' => null,
        ]);

        if( $newAttachment ) {
            RealestateActivityLog::create([
                'reservation_number' => $request->reservation_number,
                'action' => 'upload_file',
                'description' => 'Uploaded Payment Details Attachment',
                'model' => 'App\Models\Booking\Reservation',
                'properties' => null,
                'created_by' => $request->user()->id,
            ]);
        }

        return $newAttachment;
    }

    public function remove(Request $request)
    {
        // Delete file
    }
}
