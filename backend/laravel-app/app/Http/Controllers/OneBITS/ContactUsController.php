<?php

namespace App\Http\Controllers\OneBITS;

use App\Http\Controllers\Controller;
use App\Mail\OneBITS\ContactUsEmail;
use Illuminate\Http\Request;

use App\Models\Transportation\Trip;
use App\Models\Transportation\Passenger;
use App\Models\Transportation\Schedule;
use App\Models\OneBITS\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ContactUsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $contact = $request->input('contact');
        $message = $request->input('message');

        Mail::to(env('APP_ENV') == 'production' ? 'booking@1bataanits.ph' : 'bellenymeria@gmail.com')
            ->send(new ContactUsEmail($name, $email, $contact, $message));

        return 'OK';
    }
}
