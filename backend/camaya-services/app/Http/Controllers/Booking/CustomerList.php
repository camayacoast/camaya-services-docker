<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Customer;
use App\User;

class CustomerList extends Controller
{

    // protected $list;

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
        $customerList = Customer::select('id', 'object_id', 'email', 'first_name', 'last_name', 'middle_name', 'nationality', 'address', 'contact_number');

        if ($request->isTripping == true) {

            // $agentObjectIds = User::agentObjectIds();

            // $customerList->whereIn('object_id', $agentObjectIds);
            $customerList->with('user');
            
        }

        if ($request->user()->user_type === 'admin') {
            $customerList->withCount('bookings');
            $customerList->with('user.roles');
            $customerList->with('emailMatch');
        }

        return $customerList->orderBy('first_name', 'asc')
                    ->orderBy('last_name', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

    }
}
