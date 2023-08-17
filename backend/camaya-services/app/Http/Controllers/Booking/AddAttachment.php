<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use App\Models\Booking\Attachment;
use Illuminate\Http\File;

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
        // return $request->file_type;

        // $path = Storage::putFile('attachments', $request->file('file'));

        $path = $request->file('file')->store(
            '/attachments/'.$request->booking_reference_number, 'public'
        );

        $newAttachment = Attachment::create([
            'booking_reference_number' => $request->booking_reference_number,
            // 'related_id' => $request->related_id,
            // 'type' => $request->type,
            'file_name' => $request->file_name,
            'content_type' => $request->file('file')->getMimeType(),
            'file_size' => $request->file_size,
            'file_path' => env('APP_URL').Storage::url($path),
            'description' => $request->description,
            'created_by' => $request->user()->id,
            'deleted_by' => null,
        ]);

        return $newAttachment->load('uploader');
        
        // return [
        //     'path' => env('APP_URL').Storage::url($path),
        //     'file_name' => basename($path),
        // ];
    }
}
