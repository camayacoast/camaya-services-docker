<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Booking\CreatePackageRequest;
use Carbon\Carbon;
use App\Models\Booking\Package;
use App\Models\Booking\PackageInclusion;
use App\Models\Booking\PackageAllowRole;
use App\Models\Booking\PackageAllowSource;
use App\Models\Booking\PackageImage;
use App\Models\Booking\Product;
use App\Models\Hotel\RoomType;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

use DB;

class CreatePackage extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreatePackageRequest $request)
    {
        //
        // return $request->all();

        /**
         * Save the package
         */

        $connection = DB::connection('camaya_booking_db');
        $connection->beginTransaction();

        $exclude_days = [];
        
        if ($request->exclude_days) {
            foreach ($request->exclude_days as $days) {
                $exclude_days[] = date('Y-m-d', strtotime($days));
            }
        }

        // $holidays = [];

        // if ($request->holidays) {
        //     foreach ($request->holidays as $holiday) {
        //         $holidays[] = date('Y-m-d', strtotime($holiday));
        //     }
        // }

        $newPackage = Package::create([
            'name' => $request->name,
            'code' => Str::upper($request->code),
            'type' => $request->type,
            'description' => $request->description,
            'availability' => $request->availability,
            'category' => $request->category,
            'mode_of_transportation' => $request->mode_of_transportation,
            'allowed_days' => $request->allowed_days,
            'exclude_days' => $exclude_days,
            // 'holidays' => $holidays,
            'selling_start_date' => Carbon::parse(strtotime($request->selling_start_date))->setTimezone('Asia/Manila'),
            'selling_end_date' => Carbon::parse(strtotime($request->selling_end_date))->setTimezone('Asia/Manila'),
            'booking_start_date' => Carbon::parse(strtotime($request->booking_start_date))->setTimezone('Asia/Manila'),
            'booking_end_date' => Carbon::parse(strtotime($request->booking_end_date))->setTimezone('Asia/Manila'),
            'status' => $request->status,
            'regular_price' => $request->regular_price,
            'selling_price' => isset($request->weekday_rate) ? $request->weekday_rate : $request->weekend_rate, 
            'weekday_rate' => $request->weekday_rate,
            'weekend_rate' => $request->weekend_rate,
            // 'promo_rate' => $request->promo_rate,
            'walkin_price' => $request->walkin_price,
            'min_adult' => $request->min_adult,
            'max_adult' => $request->max_adult,
            'min_kid' => $request->min_kid,
            'max_kid' => $request->max_kid,
            'min_infant' => $request->min_infant,
            'max_infant' => $request->max_infant,
            'quantity_per_day' => $request->quantity_per_day,
            'stocks' => $request->stocks,
        ]);

        /**
         * Checks if package is created
         */
        if (!$newPackage) {
            $connection->rollBack();
            return response()->json(['error' => 'Package not created'], 400);
        }

        // $newPackage->refresh();

        /**
         * Package inclusions
         */

         /**
         * Add product inclusions
         */

        $package_inclusions = [];

        if (isset($request->product_inclusions)) {

            // Add updated
            $product_inclusions = collect(array_filter($request->product_inclusions))->flatMap(function ($values) {
                return $values;
            })->all();

            // return $product_inclusions;
            foreach (collect($product_inclusions)->keys()->all() as $code) {
                if ($product_inclusions[$code] > 0) {
                    $product = Product::where('code', $code)->first();

                    $package_inclusions[] = new PackageInclusion([
                        'related_id' => $product['id'],
                        'quantity' => $product_inclusions[$code] ?? null,
                        'type' => 'product',
                    ]);
                }
            }
        }


        if ($request->room_type_inclusions) {

            // $roomTypeInclusions = RoomType::whereIn('id', $request->room_type_inclusions)->get();

            foreach ([$request->room_type_inclusions] as $room_type_with_entity) {

                $split = explode("_", $room_type_with_entity);

                $id = $split[0];
                $entity = $split[1];

                $package_inclusions[] = new PackageInclusion([
                    'related_id' => $id,
                    'type' => 'room_type',
                    'entity' => $entity,
                ]);
            }
        }

        if ($package_inclusions) {
            $newPackage->packageInclusions()->saveMany($package_inclusions);
        }

         /**
         * Save product allowed roles
         */
        if ($request->allowed_roles) {
            $allowed_roles_to_save = [];

            // $roles = Role::whereIn('name', $request->allowed_roles)->get();

            // foreach ($roles as $role) {
            //     $allowed_roles_to_save[] =  new PackageAllowRole([
            //         'role_id' => $role->id
            //     ]);
            // }

            foreach ($request->allowed_roles as $role_id) {
                $allowed_roles_to_save[] =  new PackageAllowRole([
                    'role_id' => $role_id
                ]);
            }
        
            $newPackage->allowedRoles()->saveMany($allowed_roles_to_save);
        }

         /**
         * Save product allowed sources
         */
        if ($request->allowed_sources) {
            $allowed_sources_to_save = [];

            foreach ($request->allowed_sources as $source) {
                $allowed_sources_to_save[] =  new PackageAllowSource([
                    'source' => $source
                ]);
            }

            $newPackage->allowedSources()->saveMany($allowed_sources_to_save);
        }

        /**
         * Save package images
         */
        if ($request->product_images) {
            $product_images_to_save = [];

            foreach ($request->product_images as $key => $product_image) {
                $product_images_to_save[] =  new PackageImage([
                    'image_path' => $product_image,
                    'cover' => $key == 0 ? 'yes' : 'no',
                ]);
            }

            $newPackage->images()->saveMany($product_images_to_save);
        }

        $newPackage->refresh();
        $connection->commit();
        $newPackage->load('images');
        $newPackage->load('packageInclusions:id,package_id,related_id,quantity,type');

        return response()->json($newPackage, 200);

    }
}
