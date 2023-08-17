<table>
    <thead>
        <tr>
            <th colspan="10" style="font-weight: bold">Guest History Report</th>
        </tr>
        <tr>
            <th colspan="10"></th>
        </tr>
        <tr>
          <th style="width: 30px; font-weight: bold">Guest Name</th>
          <th style="width: 15px; font-weight: bold">Arrival Date</th>
          <th style="width: 15px; font-weight: bold">Departure Date</th>
          <th style="width: 15px; font-weight: bold">Number of Pax</th>
          <th style="width: 10px; font-weight: bold">Room Number</th>
          <th style="width: 10px; font-weight: bold">Room Type</th>
          <th style="width: 15px; font-weight: bold">Nationality</th>
          <th style="width: 10px; font-weight: bold">Preferences</th>
          <th style="width: 10px; font-weight: bold">Billing Instruction</th>
        </tr>
    </thead>
    <tbody>
    @foreach($reports as $report)
        <tr>
            <td>{{ $report->customer->first_name }} {{ $report->customer->first_name }}</td>
            <td>{{ date('M j, Y', strtotime($report->start_datetime)) }}</td>
            <td>{{ date('M j, Y', strtotime($report->end_datetime)) }}</td>
            <td>{{ $report->adult_pax + $report->kid_pax + $report->infant_pax }}</td>
            <td></td>
            <td></td>
            <td>{{ $report->customer->nationality }}</td>
            <td></td>
            <td></td>
        </tr>
    @endforeach
    </tbody>
</table>