<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Product;
use Illuminate\Support\Facades\Http;

use App\Models\Main\Role;

class WebsiteProductList extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        
        $customer_role = Role::where('name', 'customer')->first();

        return Product::with('images')
                ->whereHas('allowedRoles', function ($query) use ($customer_role) {
                    $query->whereIn('role_id', [$customer_role->id]);
                })
                ->where('status', 'published')
                ->get();
    }
}
