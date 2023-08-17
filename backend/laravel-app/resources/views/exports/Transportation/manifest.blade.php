<table>
    <thead>
        <tr>
            <th></th>
            <th></th>
            <th colspan="2" align="center" style="text-align:center;"><img style="display: block; margin: 0 auto;" src="images/1bits-logo.png" width="140" /></th>
            <th colspan="4"></th>
        </tr>
        <tr>
            <th colspan="10" align="center" style="font-size: 16pt; font-weight: bold; border: solid 3px black;">PASSENGER MANIFEST</th>
        </tr>
        <tr>
            <th colspan="10"><!--spacer--></th>
        </tr>
        <tr>
            <th colspan="10" style="font-size: 14pt; font-weight: bold;">Trip Information</th>
        </tr>
        <tr>
            <th colspan="10">Origin: {{$schedule->route->origin->name}}</th>            
        </tr>
        <tr>
            <th colspan="10">Destination: {{$schedule->route->destination->name}}</th>
        </tr>
        <tr>
            <th colspan="10"><!--spacer--></th>
        </tr>
        <tr>
            <th colspan="10" style="font-size: 14pt; font-weight: bold;">Schedule Information</th>
        </tr>
        <tr>
            <th colspan="10">Date: {{$schedule->trip_date}}</th>
        </tr>
        <tr>
            <th colspan="10">Departure: {{date('H:i', strtotime($schedule->start_time))}}</th>
        </tr>
        <tr>
            <th colspan="10">Arrival: {{date('H:i', strtotime($schedule->end_time))}}</th>
        </tr>
        <tr>
            <th colspan="10">Vessel: {{$schedule->transportation->name}}</th>
        </tr>
        <!-- <tr>
            <th colspan="10">Market Segmentation: {{ implode(', ', collect($schedule->seatSegments)->pluck('name')->all()) }}</th>
        </tr> -->
        <tr>
            <th colspan="10"><!--spacer--></th>
        </tr>
    </thead>
    <thead>
    <tr>
        <th style="font-weight: bold;">#</th>
        <th style="font-weight: bold;">LAST NAME</th>
        <th style="font-weight: bold;">FIRST NAME</th>
        <th style="font-weight: bold;">M.I.</th>
        <th style="font-weight: bold;">Age</th>
        <th style="font-weight: bold;">Nationality</th>
        <th style="font-weight: bold;">Address</th>
        <th style="font-weight: bold;">Contact No</th>
        <th style="font-weight: bold;">Guest Type</th>
        <th style="font-weight: bold;">Booking Tags</th>
        <th style="font-weight: bold;">Guest Tags</th>
        <th style="font-weight: bold;">Status</th>
        <th style="font-weight: bold;">Temp</th>
    </tr>
    </thead>
    <tbody>
    @foreach($trip_bookings as $key => $trip_booking)
        <tr>
            <td align="center">{{ $key+1 }}</td>
            <td align="center">{{ strtoupper($trip_booking->passenger->last_name) }}</td>
            <td align="center">{{ strtoupper($trip_booking->passenger->first_name) }}</td>
            <td align="center">{{ strtoupper($trip_booking->passenger->middle_name[0] ?? '') }}</td>
            <td align="center">{{ $trip_booking->passenger->age }}</td>
            <td align="center">{{ $trip_booking->passenger->nationality }}</td>
            <td align="center">{{ $trip_booking->booking->customer->address }}</td>
            <td align="center">{{ $trip_booking->booking->customer->contact_number }}</td>
            <td align="center">{{ $trip_booking->passenger->type }}</td>
            <td align="center">{{ implode(', ', collect($trip_booking->booking->tags)->pluck('name')->all()) }}</td>
            <td align="center">{{ implode(', ', collect($trip_booking->passenger->guest_tags)->pluck('name')->all()) }}</td>
            <td align="center">{{ $trip_booking->status }}</td>
            <td align="center"></td>
        </tr>
    @endforeach
        <tr>
            <th colspan="10" align="right" valign="middle" style="height: 40px; font-size: 12pt; font-weight: bold;">
                
                <div style="margin: 10px; font-weight: bold;">
                    <span style="display: block; margin-right: 8px; font-weight: bold;">
                        Total bookings:
                        {{
                            collect($trip_bookings)->sum(function ($tb) {
                                return in_array($tb['status'], ['arriving', 'checked_in', 'boarded', 'no_show', 'cancelled']);
                            })
                        }}
                    </span>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    @if (in_array('cancelled', $status))
                    <span style="display: block; margin-right: 8px; font-weight: bold;">
                        Cancelled:
                        {{
                            collect($trip_bookings)->sum(function ($tb) {
                                return $tb['status'] == 'cancelled';
                            })
                        }}
                    </span>
                    @endif
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    @if (in_array('no_show', $status))
                    <span style="display: block; margin-right: 8px; font-weight: bold;">
                        No show:
                        {{
                            collect($trip_bookings)->sum(function ($tb) {
                                return $tb['status'] == 'no_show';
                            })
                        }}
                    </span>
                    @endif
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    @if (in_array('checked_in', $status))
                    <span style="display: block; margin-right: 8px; font-weight: bold;">
                        Checked-in:
                        {{
                            collect($trip_bookings)->sum(function ($tb) {
                                return $tb['status'] == 'checked_in';
                            })
                        }}
                    </span>
                    @endif
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    @if (in_array('pending', $status))
                    <span style="display: block; margin-right: 8px; font-weight: bold;">
                        Checked-in:
                        {{
                            collect($trip_bookings)->sum(function ($tb) {
                                return $tb['status'] == 'pending';
                            })
                        }}
                    </span>
                    @endif
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    @if (in_array('boarded', $status))
                    <span style="display: block; margin-right: 8px; font-weight: bold;">
                        Boarded:
                        {{
                            collect($trip_bookings)->sum(function ($tb) {
                                return $tb['status'] == 'boarded';
                            })
                        }}
                    </span>
                    @endif
                </div>
            </th>
        </tr>
    </tbody>
</table>
