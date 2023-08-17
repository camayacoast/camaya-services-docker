<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;
use DB;

use App\Models\Booking\Voucher;
use App\Models\Booking\VoucherImage;

use App\Http\Requests\Booking\CreateVoucherRequest;
use Illuminate\Support\Str;

class CreateVoucher extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateVoucherRequest $request)
    {
        //
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

        $newVoucher = Voucher::create([
            'name' => $request->name,
            'code' => Str::upper($request->code),
            'type' => $request->type,
            'description' => $request->description,
            'availability' => $request->availability,
            'category' => $request->category,
            'mode_of_transportation' => $request->mode_of_transportation,
            'allowed_days' => $request->allowed_days,
            'exclude_days' => $exclude_days,
            'selling_start_date' => Carbon::parse(strtotime($request->selling_start_date))->setTimezone('Asia/Manila'),
            'selling_end_date' => Carbon::parse(strtotime($request->selling_end_date))->setTimezone('Asia/Manila'),
            'booking_start_date' => Carbon::parse(strtotime($request->booking_start_date))->setTimezone('Asia/Manila'),
            'booking_end_date' => Carbon::parse(strtotime($request->booking_end_date))->setTimezone('Asia/Manila'),
            'status' => $request->status,
            'price' => $request->price,
            'quantity_per_day' => $request->quantity_per_day,
            'stocks' => $request->stocks,
        ]);

        /**
         * Checks if package is created
         */
        if (!$newVoucher) {
            $connection->rollBack();
            return response()->json(['error' => 'Voucher not created'], 400);
        }

        // $newPackage->refresh();

        /**
         * Save package images
         */
        if ($request->voucher_images) {
            $voucher_images_to_save = [];

            foreach ($request->voucher_images as $key => $voucher_image) {
                $voucher_images_to_save[] =  new VoucherImage([
                    'image_path' => $voucher_image,
                    'cover' => $key == 0 ? 'yes' : 'no',
                ]);
            }

            $newVoucher->images()->saveMany($voucher_images_to_save);
        }

        $newVoucher->refresh();
        $connection->commit();

        $newVoucher->load('images');

        return response()->json($newVoucher, 200);
    }
}
