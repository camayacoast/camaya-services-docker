<table>
    <thead>
        <tr>
            <th colspan="10" style="font-weight: bold">Daily Arrival Report</th>
        </tr>
        <tr>
            <th colspan="10"></th>
        </tr>
        <tr>
            <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Total Arrival Rooms</th>
            <td>{{ count($reports['room_reservations']) }}</td>
            <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Total Stay-over Rooms</th>
            <td>{{ $reports['stayover_room_total'] ?? 0 }}</td>
            <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Total House-use Rooms</th>
            <td>{{ $reports['houseuse_room_total'] ?? 0 }}</td>
        </tr>
        <tr>
            <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Total No. of Pax</th>
            <td>{{ collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.adult_pax") + collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.kid_pax") + collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.infant_pax") }}</td>
            <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Total No. of Pax</th>
            <td>{{ $reports['stayover_pax_total'] ?? 0 }}</td>
            <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Total No. of Pax</th>
            <td>{{ $reports['houseuse_pax_total'] ?? 0 }}</td>
        </tr>
        <tr>
            <th style="height: 30px; width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Room No.</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Room Type</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Booking Code</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Check In Time</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Room Status</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Last Name</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Guest Name</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Adult</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Child</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Infant</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Total No. of Pax</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Billing Instructions</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Rate Code</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Market Segmentation</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Arrival Date</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Departure Date</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Transpo Details</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Remarks</th>
        </tr>
    </thead>
    <tbody>
    <?php

        $room_status = [
            'clean' => 'C',
            'clean_inspected' => 'CI',
            'dirty' => 'D',
            'dirty_inspected' => 'DI',
            'pickup' => 'P',
            'sanitized' => 'S',
            'inspected' => 'I',
            'out-of-service' => 'OS',
            'out-of-order' => 'OO',
            'none' => '',
        ];

    ?>
    @foreach($reports['room_reservations'] as $report)
        <tr>
            <td>{{ $report->room->number }}</td>
            <td>{{ $report->room_type->code }}</td>
            <td>{{ $report->booking->reference_number }}</td>
            <td>{{ $report->check_in_time ? date('h:i:s a', strtotime($report->check_in_time)) : '' }}</td>
            <td>{{ \Str::upper(\Str::of($report->room->fo_status)->substr(0,1)) }}{{ $room_status[$report->room->room_status ? $report->room->room_status : 'none'] }}</td>
            <td>{{ $report->booking->customer->last_name }}</td>
            <td>{{ $report->booking->customer->first_name }}</td>
            <td>{{ $report->booking->adult_pax }}</td>
            <td>{{ $report->booking->kid_pax }}</td>
            <td>{{ $report->booking->infant_pax }}</td>
            <td>{{ $report->booking->adult_pax + $report->booking->kid_pax + $report->booking->infant_pax }}</td>
            <td>{{ $report->booking->billing_instructions }}</td>
            <td>-</td>
            <td>{{ implode(", ", collect($report->booking->tags)->pluck("name")->all()) }}</td>
            <td>{{ date('d-M-y', strtotime($report->booking->start_datetime)) }}</td>
            <td>{{ date('d-M-y', strtotime($report->booking->end_datetime)) }}</td>
            <td>{{ $report->booking->mode_of_transportation }}</td>
            <td>{{ $report->booking->remarks }}</td>
            <td></td>
        </tr>
    @endforeach
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="width: 150px; font-weight: bold; background: yellow; text-align: right;" valign="middle">{{ collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.adult_pax") }}</td>
            <td style="width: 150px; font-weight: bold; background: yellow; text-align: right;" valign="middle">{{ collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.kid_pax") }}</td>
            <td style="width: 150px; font-weight: bold; background: yellow; text-align: right;" valign="middle">{{ collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.infant_pax") }}</td>
            <td style="width: 150px; font-weight: bold; background: yellow; text-align: right;" valign="middle">{{ collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.adult_pax") + collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.kid_pax") + collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.infant_pax") }}</td>
        </tr>
    </tbody>
</table>