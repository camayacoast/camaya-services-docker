<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Voucher;
use App\Models\Booking\VoucherImage;

use App\Http\Requests\Booking\UpdateVoucherStubRequest;
use Illuminate\Support\Str;
use DB;
use Carbon\Carbon;

class UpdateVoucherStub extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(UpdateVoucherStubRequest $request)
    {
        //
        $connection = DB::connection('camaya_booking_db');
        $connection->beginTransaction();

        $exclude_days = [];
        
        if ($request->exclude_days) {
            foreach ($request->exclude_days as $days) {
                $exclude_days[] = date('Y-m-d', strtotime($days));
            }
        }

        $updateVoucher = Voucher::where('id', $request->id)
        ->update([
            'name' => $request->name,
            // 'code' => Str::upper($request->code),
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
        if (!$updateVoucher) {
            $connection->rollBack();
            return response()->json(['error' => 'Voucher not updated'], 400);
        }

        // $newPackage->refresh();

        /**
         * Save package images
         */
        // if ($request->voucher_images) {
        //     $voucher_images_to_save = [];

        //     foreach ($request->voucher_images as $key => $voucher_image) {
        //         $voucher_images_to_save[] =  new VoucherImage([
        //             'image_path' => $voucher_image,
        //             'cover' => $key == 0 ? 'yes' : 'no',
        //         ]);
        //     }

        //     $updateVoucher->images()->saveMany($voucher_images_to_save);
        // }

        // $updateVoucher->refresh();
        $connection->commit();

        // $updateVoucher->load('images');

        $voucher = Voucher::where('id', $request->id)->first();

        return response()->json($voucher, 200);
    }
}
