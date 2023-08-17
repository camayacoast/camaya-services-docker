<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\LandAllocation as ModelLandAllocation;

class LandAllocation extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        return ModelLandAllocation::where('date', $request->date)
                        ->orderBy('date', 'desc')
                        ->with('allowed_users.user')
                        ->with('owner')
                        ->get();
    }
}
