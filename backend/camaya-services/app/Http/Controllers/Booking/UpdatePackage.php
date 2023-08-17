<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Http\Requests\Booking\CreatePackageRequest;
use App\Models\Booking\Package;
use App\Models\Booking\PackageInclusion;
use App\Models\Booking\PackageAllowRole;
use App\Models\Booking\PackageAllowSource;
use App\Models\Booking\PackageImage;
use App\Models\Booking\Product;
use App\Models\Hotel\RoomType;

use DB;

class UpdatePackage extends Controller
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

        $updatedPackage = Package::find($request->id);

        $updatedPackage->update([
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
            'selling_price' => isset($request->weekday_rate)?$request->weekday_rate : $request->weekend_rate,
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
        // if (!$newPackage) {
        //     $connection->rollback();
        //     return response()->json(['error' => 'Package not created'], 400);
        // }

        $updatedPackage->refresh();

        /**
         * Package inclusions
         */

        $package_inclusions = [];
        
        $existingProductInclusions = $updatedPackage->packageInclusions()->get()->toArray();        
        $existingRoomTypeInclusions = $updatedPackage->packageRoomTypeInclusions()->get()->toArray();        
        $isPackageExist = function($packageToCheck, $existingPackages) {
            foreach($existingPackages as $package) {
                if ($package['related_id'] == $packageToCheck['related_id']) {
                    return true;
                }
            }

            return false;
        };

        /**
         * Update product inclusions
         */

        if (isset($request->product_inclusions)) {

            
            // Delete all product inclusions
             PackageInclusion::where('package_id', $updatedPackage->id)
                        ->where('type', 'product')
                        ->delete();


            // Add updated
            $product_inclusions = collect(array_filter($request->product_inclusions))->flatMap(function ($values) {
                return $values;
            })->all();

            // return $product_inclusions;
            foreach (collect($product_inclusions)->keys()->all() as $code) {
                if ($product_inclusions[$code] > 0) {
                    $product = Product::where('code', $code)->first();

                    PackageInclusion::create([
                        'package_id' => $updatedPackage->id,
                        'related_id' => $product['id'],
                        'quantity' => $product_inclusions[$code],
                        'type' => 'product',
                    ]);
                }
            }
        }
        

        if ($request->room_type_inclusions) {
            // $roomTypeInclusions = RoomType::whereIn('id', $request->room_type_inclusions)->get();

            /**
             * Room type with allocation is only 1:1 for Package and room type allocation
             */

            // Delete all room_type inclusions for this package. package_id = $request->id
            PackageInclusion::where('package_id', $request->id)->where('type', 'room_type')->delete();

            foreach ([$request->room_type_inclusions] as $room_type_with_entity) {

                $split = explode("_", $room_type_with_entity);

                $id = $split[0];
                $entity = $split[1];

                // if (!$isPackageExist(['related_id' => $i->id,'type' => 'room_type'], $existingRoomTypeInclusions)) {
                    $package_inclusions[] = new PackageInclusion([
                        'related_id' => $id,
                        'type' => 'room_type',
                        'entity' => $entity,
                    ]);                    
                // }
            }            
        }
        

        if ($package_inclusions) {
            $updatedPackage->packageInclusions()->saveMany($package_inclusions);
        }

         /**
         * Save product allowed roles
         */
        if (isset($request->allowed_roles)) {
            $allowed_roles_to_save = [];

            $existingAllowedRoles = $updatedPackage->allowedRoles()->get()->pluck('role_id')->toArray();
            $rolesToBeMaintained = [];

            foreach ($request->allowed_roles as $role_id) {
                if (in_array($role_id, $existingAllowedRoles)) {
                    $rolesToBeMaintained[] = $role_id;
                } else {
                    $allowed_roles_to_save[] =  new PackageAllowRole([
                        'role_id' => $role_id
                    ]);
                }
            }

            // delete roles removed by the user
            $updatedPackage
                ->allowedRoles()
                ->whereNotIn('role_id', $rolesToBeMaintained)
                ->delete();
            
            if ($allowed_roles_to_save) {
                $updatedPackage->allowedRoles()->saveMany($allowed_roles_to_save);
            }
        }

         /**
         * Save product allowed sources
         */
        if (isset($request->allowed_sources)) {
            $allowed_sources_to_save = [];
            $existingAllowedSources = $updatedPackage->allowedSources()->get()->pluck('source')->toArray();
            $allowed_sources_to_be_maintained = [];

            foreach ($request->allowed_sources as $source) {
                if (in_array($source, $existingAllowedSources)) {
                    $allowed_sources_to_be_maintained[] = $source;
                } else {
                    $allowed_sources_to_save[] =  new PackageAllowSource([
                        'source' => $source
                    ]);
                }
            }

            // delete source removed by the user
            $updatedPackage
                ->allowedSources()
                ->whereNotIn('source', $allowed_sources_to_be_maintained)
                ->delete();

            if ($allowed_sources_to_save) {
                $updatedPackage->allowedSources()->saveMany($allowed_sources_to_save);
            }
        }

        /**
         * Save package images
         */
        // if (isset($request->product_images)) {
        //     $product_images_to_save = [];
        //     $existingProductImages = $updatedPackage->images()->get()->pluck('image_path')->toArray();
        //     $allowed_producut_images_to_be_maintained = [];

        //     foreach ($request->product_images as $key => $product_image) {
        //         if (in_array($product_image, $existingProductImages)) {
        //             $allowed_producut_images_to_be_maintained[] = $product_image;
        //         } else {
        //             $product_images_to_save[] =  new PackageImage([
        //                 'image_path' => $product_image,
        //                 'cover' => $key == 0 ? 'yes' : 'no',
        //             ]);
        //         }
        //     }

        //     // delete images data removed by the user
        //     $updatedPackage
        //         ->images()
        //         ->whereNotIn('image_path', $allowed_producut_images_to_be_maintained)
        //         ->delete();

        //     if ($product_images_to_save) {
        //         $updatedPackage->images()->saveMany($product_images_to_save);
        //     }

        //     // set all product images cover to no
        //     $updatedPackage
        //         ->images()
        //         ->update(['cover' => 'no']);

        //     // set the first product image cover to yes
        //     $firstProductImageRecord = $updatedPackage
        //         ->images()
        //         ->orderBy('id')
        //         ->first();
            
        //     if ($firstProductImageRecord) {
        //         $firstProductImageRecord->update(['cover' => 'yes']);
        //     }
        // }

        $updatedPackage->refresh();
        // $connection->commit();
        $updatedPackage->load(
            'images', 
            'allowedRoles:id,package_id,role_id', 
            'allowedSources:id,package_id,source',
            'packageInclusions:id,package_id,related_id,quantity,type',
            'packageRoomTypeInclusions:id,package_id,related_id,quantity,type,entity'
        );

        $package = $updatedPackage;
        $package['allowed_roles'] = $updatedPackage->allowedRoles()->pluck('role_id')->toArray();

        $connection->commit();
        return response()->json($package, 200);
    }
}
