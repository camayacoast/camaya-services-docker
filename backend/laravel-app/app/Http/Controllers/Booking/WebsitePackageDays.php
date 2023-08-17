<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Package;
use App\Models\Main\Role;

use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\Room;

use Carbon\CarbonPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebsitePackageDays extends Controller
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

        $customer_role = Role::whereIn('name', ['customer', 'Customer'])->first();

        $packages = Package::select(
                'name',
                'code',
                'allowed_days',
                'exclude_days',
                'selling_end_date',
                'selling_start_date',
            )
            ->join('package_allow_roles', 'packages.id', '=', 'package_allow_roles.package_id')
            ->whereIn('package_allow_roles.role_id', [$customer_role->id])
            // ->whereHas('allowedRoles', function ($query) use ($customer_role) {
            //     $query->whereIn('role_id', [$customer_role->id]);
            // })
            ->where('status', 'published')
            ->where('selling_start_date', '<=', Carbon::now()->toDateString())
            ->where('selling_end_date', '>=', Carbon::now()->toDateString())
            ->get();

        $included_days = [];
        $excluded_days = [];
        foreach ($packages as $package) {
            $allowed_days = collect($package->allowed_days)->map(function ($dayName) {
                switch ($dayName) {   
                    case 'sun':
                        return 0;                 
                    case 'mon':
                        return 1;
                    case 'tue':
                        return 2;
                    case 'wed':
                        return 3;
                    case 'thu':
                        return 4;
                    case 'fri':
                        return 5;
                    case 'sat':
                        return 6;                                            
                }
            })->toArray();

            $period = CarbonPeriod::create($package->selling_start_date, $package->selling_end_date);
            
            foreach ($period as $date) {
                if (in_array($date->dayOfWeek, $allowed_days) && !in_array($date->toDateString(), $included_days) && $date->greaterThanOrEqualTo(Carbon::now())) {
                    $included_days[] = $date->toDateString();
                }
            }

            foreach ($package->exclude_days as $exclude_day) {
                $exclude_date = Carbon::create($exclude_day);
                if (!in_array($exclude_day, $excluded_days) && $exclude_date->greaterThanOrEqualTo(Carbon::now())) {
                    $excluded_days[] = $exclude_day;
                }
            }
        }

        return response()->json(
            compact(
                'included_days', 
                'excluded_days',
                'packages',
            ), 
            200
        );        
    }
}
