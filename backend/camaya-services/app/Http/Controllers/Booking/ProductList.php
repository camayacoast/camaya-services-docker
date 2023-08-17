<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Product;
use App\Models\Booking\ProductAllowRole;
use App\Models\Booking\ProductPass;

use App\Models\Main\Role;

class ProductList extends Controller
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

        $override_roles = ['super-admin', 'IT'];

        if ($request->user()->hasRole($override_roles)) {
            return Product::with(['images', 'allowedSources:id,product_id,source'])
                        ->with('allowedRoles.role')
                        ->with(['productPass' => function ($q) {
                            $q->select('stub_id', 'product_id');
                        }])
                        ->orderBy('name')
                        ->get();
        }

        $role_ids = $request->user()->roles->pluck('id');
        
        return Product::with(['images', 'allowedSources:id,product_id,source'])
                ->whereHas('allowedRoles', function ($query) use ($role_ids) {
                    $query->whereIn('role_id', $role_ids);
                })
                ->where('status', 'published')
                ->orderBy('name')
                ->get();
    }
}
