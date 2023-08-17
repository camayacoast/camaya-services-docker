<table>
  <thead>
      <tr>
          <th colspan="10" style="font-weight: bold">In-House Guest List</th>
      </tr>
      <tr>
          <th colspan="10"></th>
      </tr>
      <tr>
        <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Total In-House Guest</th>
        <td>{{ collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.adult_pax") + collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.kid_pax") + collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.infant_pax") }}</td>

        <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Adult</th>
        <td>{{ collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.adult_pax") }}</td>

        <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Kid</th>
        <td>{{ collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.kid_pax") }}</td>

        <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Infant</th>
        <td>{{ collect(collect($reports['room_reservations'])->unique('booking_reference_number')->values()->all())->sum("booking.infant_pax") }}</td>
      </tr>
      <tr>
            <th style="height: 30px; font-weight: bold; background: yellow; text-align: center;">Total Rooms</th>
            <td>
                {{ count($reports['room_reservations']) }}
            </td>
      </tr>
      
      <tr>
          <th style="height: 30px; width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Room No.</th>
          <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Room Type</th>
          <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Booking Code</th>
          <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Last Name</th>
          <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Guest Name</th>
          <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Type</th>
          <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Age</th>
          <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Arrival Date</th>
          <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Departure Date</th>
          <th style="width: 150px; font-weight: bold; background: yellow; text-align: center;" valign="middle">Remarks</th>
      </tr>
  </thead>
  <tbody>
    @foreach($reports['stayOver_guests'] as $report)
        <tr>
            <td style="text-align: left;">
                {{ implode(", ", collect($report->booking->room_reservations_no_filter)->map(function ($item, $key) {
                    return $item['room']['number'];
                })->all()) }}
            </td>
            <td>
                {{ implode(", ", collect($report->booking->room_reservations_no_filter)->map(function ($item, $key) {
                    return $item['room_type']['code'];
                })->all()) }}
            </td>
            <td>{{ $report->reference_number }}</td>
            <td>{{ $report->last_name }}</td>
            <td>{{ $report->first_name }}</td>
            <td>{{ $report->type }}</td>
            <td style="text-align: left;">{{ $report->age }}</td>
            <td>{{ date('d-M-y', strtotime($report->start_datetime)) }}</td>
            <td>{{ date('d-M-y', strtotime($report->end_datetime)) }}</td>
            <td>{{ $report->booking->remarks }}</td>
            <td></td>
        </tr>
    @endforeach
  </tbody>
</table>