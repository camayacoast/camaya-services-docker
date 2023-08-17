<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Booking\CreateProductRequest;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

use App\Models\Booking\Product;
use App\Models\Booking\ProductAllowRole;
use App\Models\Booking\ProductAllowSource;
use App\Models\Booking\ProductImage;
use App\Models\Booking\ProductPass;

class CreateProduct extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateProductRequest $request)
    {
        //

        // return $request->all();

        /**
         * Save Product
         */ 

        $newProduct = Product::create([
            'name' => $request->name,
            'code' => Str::upper($request->code),
            'type' => $request->type,
            'category' => $request->category,
            'availability' => $request->availability,
            'status' => $request->status,
            'serving_time' => $request->serving_time,
            'quantity_per_day' => $request->quantity_per_day,
            'price' => $request->price,
            'walkin_price' => $request->walkin_price,
            'kid_price' => $request->kid_price,
            'infant_price' => $request->infant_price,
            'description' => $request->description,
            'auto_include' => $request->auto_include,
            'addon_of' => $request->addon_of,
        ]);

        $newProduct->refresh();

        /**
         * Save product allowed roles
         */
        if (isset($request->allowed_roles)) {
            $allowed_roles_to_save = [];

            // $roles = Role::whereIn('name', $request->allowed_roles)->get();

            // foreach ($roles as $role) {
            //     $allowed_roles_to_save[] =  new ProductAllowRole([
            //         'role_id' => $role->id
            //     ]);
            // }
            
            foreach ($request->allowed_roles as $role_id) {
                $allowed_roles_to_save[] =  new ProductAllowRole([
                    'role_id' => $role_id
                ]);
            }
        
            $newProduct->allowedRoles()->saveMany($allowed_roles_to_save);
        }

         /**
         * Save product allowed sources
         */
        if ($request->allowed_sources) {
            $allowed_sources_to_save = [];

            foreach ($request->allowed_sources as $source) {
                $allowed_sources_to_save[] =  new ProductAllowSource([
                    'source' => $source
                ]);
            }

            $newProduct->allowedSources()->saveMany($allowed_sources_to_save);
        }

        /**
         * Save product images
         */
        if ($request->product_images) {
            $product_images_to_save = [];

            foreach ($request->product_images as $key => $product_image) {
                $product_images_to_save[] =  new ProductImage([
                    'image_path' => $product_image,
                    'cover' => $key == 0 ? 'yes' : 'no',
                ]);
            }

            $newProduct->images()->saveMany($product_images_to_save);
        }

        /**
         * Save stubs as passes
         */
        if (isset($request->product_pass)) {
            $stubs_to_save = [];
            
            foreach ($request->product_pass as $pass_stub) {

                // $stub = Stub::where('id', $pass_stub)->first();

                $stubs_to_save[] =  new ProductPass([
                    'stub_id' => $pass_stub,
                ]);
            }
        
            $newProduct->productPass()->saveMany($stubs_to_save);
        }


        $newProduct->refresh();

        $newProduct->load(
            'images', 
            'allowedRoles:id,product_id,role_id', 
            'allowedSources:id,product_id,source',
        );

        return response()->json($newProduct, 200);

    }
}
