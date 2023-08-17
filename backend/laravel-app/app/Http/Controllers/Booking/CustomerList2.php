<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Customer;

class CustomerList2 extends Controller
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

        $customerList = Customer::select('id', 'object_id', 'email', 'first_name', 'last_name', 'middle_name', 'nationality', 'address', 'contact_number');

        $customerList->whereRaw("CONCAT( first_name,  ' ', last_name ) LIKE  '%".$request->search."%'");
            
        $customerList->orWhere('email', 'LIKE', '%'.$request->search.'%');

        if ($request->isTripping == true) {

            // $agentObjectIds = User::agentObjectIds();

            // $customerList->whereIn('object_id', $agentObjectIds);
            $customerList->with('user');
            
        }

        return $customerList->orderBy('first_name', 'asc')
                    ->orderBy('last_name', 'asc')
                    ->orderBy('id', 'asc')
                    ->take(20)
                    ->get();
    }
}
