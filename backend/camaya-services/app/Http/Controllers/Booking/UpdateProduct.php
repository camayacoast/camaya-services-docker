<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

use App\Models\Booking\Product;
use App\Models\Booking\ProductAllowRole;
use App\Models\Booking\ProductAllowSource;
use App\Models\Booking\ProductImage;
use App\Models\Booking\ProductPass;

class UpdateProduct extends Controller
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
         * Update Product
         */ 

        $updatedProduct = Product::find($request->id);
        
        $updatedProduct->update([
            'name' => $request->name,
            'code' => Str::upper($request->code),
            'type' => $request->type,
            'category' => $request->category,
            'availability' => $request->availability,
            'status' => $request->status,
            'serving_time' => $request->serving_time,
            'allowed_days' => $request->allowed_days,
            'quantity_per_day' => $request->quantity_per_day,
            'price' => $request->price,
            'walkin_price' => $request->walkin_price,
            'kid_price' => $request->kid_price,
            'infant_price' => $request->infant_price,
            'description' => $request->description,
            'auto_include' => $request->auto_include,
            'addon_of' => $request->addon_of,
        ]);

        $updatedProduct->refresh();

        /**
         * Save product allowed roles
         */
        if (isset($request->allowed_roles)) {
            $allowed_roles_to_save = [];

            $existingAllowedRoles = $updatedProduct->allowedRoles()->get()->pluck('role_id')->toArray();
            $rolesToBeMaintained = [];

            foreach ($request->allowed_roles as $role_id) {
                if (in_array($role_id, $existingAllowedRoles)) {
                    $rolesToBeMaintained[] = $role_id;
                } else {
                    $allowed_roles_to_save[] = new ProductAllowRole([
                        'role_id' => $role_id
                    ]);                                        
                }
            }
            
            // delete roles removed by the user
            $updatedProduct
                ->allowedRoles()
                ->whereNotIn('role_id', $rolesToBeMaintained)
                ->delete();

            if ($allowed_roles_to_save) {
                $updatedProduct->allowedRoles()->saveMany($allowed_roles_to_save);
            }
        }

         /**
         * Save product allowed sources
         */
        if (isset($request->allowed_sources)) {
            $allowed_sources_to_save = [];
            $existingAllowedSources = $updatedProduct->allowedSources()->get()->pluck('source')->toArray();
            $allowed_sources_to_be_maintained = [];

            foreach ($request->allowed_sources as $source) {
                if (in_array($source, $existingAllowedSources)) {
                    $allowed_sources_to_be_maintained[] = $source;
                } else {
                    $allowed_sources_to_save[] = new ProductAllowSource([
                        'source' => $source
                    ]);
                }
            }

            // delete source removed by the user
            $updatedProduct
                ->allowedSources()
                ->whereNotIn('source', $allowed_sources_to_be_maintained)
                ->delete();

            if ($allowed_sources_to_save) {
                $updatedProduct->allowedSources()->saveMany($allowed_sources_to_save);
            }
        }

        /**
         * Save stubs as passes
         */
        if (isset($request->product_pass)) {

            $stubs_to_save = [];

            // Delete all product pass
            ProductPass::where('product_id', $request->id)->delete();
            
            foreach ($request->product_pass as $pass_stub) {
                $stubs_to_save[] =  new ProductPass([
                    'stub_id' => $pass_stub,
                ]);
            }
        
            $updatedProduct->productPass()->saveMany($stubs_to_save);
        }

        /**
         * Save product images
         */
        // if (isset($request->product_images)) {
        //     $product_images_to_save = [];
        //     $existingProductImages = $updatedProduct->images()->get()->pluck('image_path')->toArray();
        //     $allowed_producut_images_to_be_maintained = [];

        //     foreach ($request->product_images as $key => $product_image) {
        //         if (in_array($product_image, $existingProductImages)) {
        //             $allowed_producut_images_to_be_maintained[] = $product_image;
        //         } else {
        //             $product_images_to_save[] = new ProductImage([
        //                 'image_path' => $product_image,
        //                 'cover' => $key == 0 ? 'yes' : 'no',
        //             ]);
        //         }
        //     }

        //     // delete images data removed by the user
        //     $updatedProduct
        //         ->images()
        //         ->whereNotIn('image_path', $allowed_producut_images_to_be_maintained)
        //         ->delete();

        //     if ($product_images_to_save) {
        //         $updatedProduct->images()->saveMany($product_images_to_save);
        //     }

        //     // set all product images cover to no
        //     $updatedProduct
        //         ->images()
        //         ->update(['cover' => 'no']);

        //     // set the first product image cover to yes
        //     $firstProductImageRecord = $updatedProduct
        //         ->images()
        //         ->orderBy('id')
        //         ->first();
            
        //     if ($firstProductImageRecord) {
        //         $firstProductImageRecord->update(['cover' => 'yes']);
        //     }
        // }

        $updatedProduct->refresh();
        
        $updatedProduct->load(
            'images', 
            'allowedRoles:id,product_id,role_id', 
            'allowedSources:id,product_id,source',
            'productPass:stub_id,product_id'
        );        

        return response()->json($updatedProduct, 200);
    }
}
