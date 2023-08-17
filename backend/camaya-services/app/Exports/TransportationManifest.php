<?php

namespace App\Exports;

use App\Models\Transportation\Trip;
use App\Models\Transportation\Schedule;
// use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class TransportationManifest implements FromView, WithColumnWidths
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
            'B' => 20,        
            'C' => 20,
            'D' => 5,
            'E' => 5,
            'F' => 10,
            'G' => 10,
            'H' => 10,
        ];
    }

    public function view(): View
    {
        $schedule = Schedule::where('trip_number', $this->trip_number)
                            ->with('route.origin')
                            ->with('route.destination')
                            ->with('transportation')
                            ->with('seatSegments')
                            ->first();

        $trip_bookings = Trip::where('trip_number', $this->trip_number)
                        ->with('passenger.guest_tags')
                        ->with('booking.customer')
                        ->with('booking.tags')
                        ->whereIn('status', $this->status)
                        ->where('ticket_reference_number', '1')
                        ->get();
        
        return view('exports.Transportation.manifest', [
            'schedule' => $schedule,
            'trip_bookings' => $trip_bookings,
            'status' => $this->status
        ]);
    }

}
