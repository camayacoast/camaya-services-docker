<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Note;
use App\Models\Booking\Booking;

class NewNote extends Controller
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

        $booking = Booking::where('reference_number', $request->booking_reference_number)->first();

        if (!$booking) {
            return response()->json(['error' => 'BOOKING_NOT_FOUND'], 400);
        }

        $newNote = Note::create([
            'booking_reference_number' => $booking->reference_number,
            'author' => $request->user()->id,
            'message' => $request->message
        ]);

        return $newNote->load('author_details');

    }
}
