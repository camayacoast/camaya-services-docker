<table>
    <thead>
        <tr>
            <th colspan="10" style="font-weight: bold">Daily Departure Report</th>
        </tr>
        <tr>
            <th colspan="10"></th>
        </tr>
        {{-- <tr>
            <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Total No. of Pax</th>
            <td>{{ collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.adult_pax") + collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.kid_pax") + collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.infant_pax") }}</td>
        </tr> --}}
        <tr>
            <th style="height: 30px; width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Room No.</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Room Type</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Booking Code</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Last Name</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Guest Name</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Adult</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Child</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Infant</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Total No. of Pax</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Arrival Date</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Departure Date</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Remarks</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Checkout Time</th>
            <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Checked-out By</th>
        </tr>
    </thead>
    <tbody>
    @foreach($reports['room_reservations'] as $report)
        <tr>
            <td>{{ $report->room->number }}</td>
            <td>{{ $report->room_type->code }}</td>
            <td>{{ $report->booking->reference_number }}</td>
            <td>{{ $report->booking->customer->last_name }}</td>
            <td>{{ $report->booking->customer->first_name }}</td>
            <td>{{ $report->booking->adult_pax }}</td>
            <td>{{ $report->booking->kid_pax }}</td>
            <td>{{ $report->booking->infant_pax }}</td>
            <td>{{ $report->booking->adult_pax + $report->booking->kid_pax + $report->booking->infant_pax }}</td>
            <td>{{ date('d-M-y', strtotime($report->booking->start_datetime)) }}</td>
            <td>{{ date('d-M-y', strtotime($report->booking->end_datetime)) }}</td>
            <td>{{ $report->booking->remarks }}</td>
            <td>{{ $report->check_out_time ? date('h:i:s a', strtotime($report->check_out_time)) : '' }}</td>
            <td>{{ $report->checked_out_by_details ? $report->checked_out_by_details->first_name.' '.$report->checked_out_by_details->last_name : '-' }}</td>
        </tr>
    @endforeach
        <tr>
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