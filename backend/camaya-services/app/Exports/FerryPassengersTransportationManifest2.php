<?php

namespace App\Exports;

use App\Models\Main\Role;
use App\Models\Transportation\Trip;
use App\Models\Transportation\Schedule;
// use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class FerryPassengersTransportationManifest2 implements FromView, WithColumnWidths, ShouldAutoSize, WithDrawings
{

    protected $trip_number;
    protected $status;

    public function __construct($trip_number, $status)
    {
        $this->trip_number = $trip_number;
        $this->status = $status;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 15,
            'C' => 15,
            'D' => 5,
            'E' => 5,
            'F' => 15,
            'G' => 25,
            'H' => 15,
            'I' => 20,
            'J' => 20,
            'K' => 25,
            'L' => 10,
            'M' => 10,            
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('logo');
        $drawing->setPath(public_path('images/magic-leaf-logo.png'));
        $drawing->setCoordinates('G1');
        $drawing->setOffsetX(150);
        $drawing->setHeight(70);

        return $drawing;
    }

    public function view(): View
    {
        if (count($this->status) === 1 && $this->status[0] === 'arriving') {
            $status = [
                'pending', 
                'checked_in', 
                'boarded', 
                'no_show', 
                'cancelled', 
                'arriving',
            ];
        } else {
            $status = $this->status;
        }

        $schedule = Schedule::where('trip_number', $this->trip_number)
            ->with('route.origin')
            ->with('route.destination')
            ->with('transportation')
            ->with('seatAllocations')
            ->with('seatAllocations.segments')
            ->first();

        $trip_bookings = Trip::where('trip_number', $this->trip_number)
            ->with('passenger.guest_tags')
            ->with('booking.customer')
            ->with('booking.tags')
            ->with('seatSegments')
            ->with('seatSegments.seat_allocation')
            ->with('seatSegments.allowed_roles')
            ->with('seatSegments.allowed_users')
            ->with('seatSegments.trips')
            ->whereIn('status', $status)
            ->where('ticket_reference_number', '1')
            ->get();

        $trip_bookings->map(function ($trip_booking) {
            $market_segmentation = '';

            foreach($trip_booking->seatSegments as $seat_segment) {
                $allowed_users = collect($seat_segment->allowed_users)->pluck('user_id')->all();
                // // $allowed_roles = Role::find(collect($seat_segment->allowed_roles)->pluck('role_id')->all())->pluck('name')->all();

                // // if (in_array('Sales Director', $allowed_roles)) {
                // //     $market_segmentation = $seat_segment->seat_allocation->name . ', ' . $seat_segment->name;
                // // }
                
                // // $seat_segment_trip_ids = collect($seat_segment->trips)->pluck('id')->all();
                // if (in_array($trip_booking->booking->created_by, $allowed_users)) {
                //     $market_segmentation = $seat_segment->seat_allocation->name . ', ' . $seat_segment->name;
                // }

                if ($trip_booking->seat_segment_id === $seat_segment->id) {
                    $market_segmentation = $seat_segment->seat_allocation->name . ', ' . $seat_segment->name;
                }
            }

            $trip_booking->market_segmentation =  $market_segmentation;
            return $trip_booking;
        });

        // Log::debug($trip_bookings);

        return view('exports.Transportation.ferryPassengersManifest2', [
            'schedule' => $schedule,
            'trip_bookings' => $trip_bookings,
            'trip_numbers' => $this->trip_number,
            'status' => $this->status
        ]);
    }
}
