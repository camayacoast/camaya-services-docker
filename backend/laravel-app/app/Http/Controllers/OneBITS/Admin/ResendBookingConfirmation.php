<?php

namespace App\Http\Controllers\OneBITS\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Trip;
use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\Passenger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\OneBITS\NewBooking;

class ResendBookingConfirmation extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $tickets = Ticket::where('group_reference_number', $request->group_reference_number)->get();
        $passenger_email = $tickets[0]['email'];
        Mail::to($passenger_email)
        // ->cc($additional_emails)
        ->send(new NewBooking($request->group_reference_number, $tickets));
    }
}
