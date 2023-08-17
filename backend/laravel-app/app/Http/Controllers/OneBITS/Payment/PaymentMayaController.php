<?php

namespace App\Http\Controllers\OneBITS\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\OneBITS\Ticket;
use Carbon\Carbon;
use PayMaya\PayMayaSDK;
use App\Models\Transportation\Trip;
use Illuminate\Support\Facades\Mail;
use App\Mail\OneBITS\NewBooking;

class PaymentMayaController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

    }
}
