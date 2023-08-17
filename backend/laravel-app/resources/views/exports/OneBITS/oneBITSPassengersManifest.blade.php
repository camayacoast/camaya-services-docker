<table>
    <thead>
        <tr>
            <th colspan="13" height="50">
                <!-- <img src="images/1bits-logo.png" width="140" /> -->
            </th>
        </tr>
        <tr>
            <th colspan="13" align="center" style="font-size: 16pt; font-weight: bold; border: solid 3px black;">PASSENGER MANIFEST</th>
        </tr>
        <tr>
            <th colspan="13"><!--spacer--></th>
        </tr>
        <tr>
            <th colspan="13" style="font-size: 14pt; font-weight: bold;">Trip Information</th>
        </tr>
        <tr>
            <th colspan="13">Origin: {{$schedule->route->origin->name}}</th>            
        </tr>
        <tr>
            <th colspan="13">Destination: {{$schedule->route->destination->name}}</th>
        </tr>
        <tr>
            <th colspan="13"><!--spacer--></th>
        </tr>
        <tr>
            <th colspan="13" style="font-size: 14pt; font-weight: bold;">Schedule Information</th>
        </tr>
        <tr>
            <th colspan="13">Date: {{ $date }}</th>
        </tr>
        <tr>
            <th colspan="13">Departure: {{ $schedule->start_time }}</th>
        </tr>
        <tr>
            <th colspan="13">Arrival: {{ $schedule->end_time }}</th>
        </tr>
        <tr>
            <th colspan="13">Vessel: {{ $schedule->transportation->name}} </th>
        </tr>        
        <tr>
            <th colspan="13"><!--spacer--></th>
        </tr>
    </thead>    
    <tbody>                
    <tr>
                                                             
            <tr>
                <th align="center" style="font-weight: bold;">#</th>
                <th align="center" style="font-weight: bold;">Ticket Ref. no.</th>
                <th align="center" style="font-weight: bold;">Booking Ref. no.</th>
                <th align="center" style="font-weight: bold;">Trip</th>
                <th align="center" style="font-weight: bold;">Firstname</th>
                <th align="center" style="font-weight: bold;">Lastname</th>
                <th align="center" style="font-weight: bold;">Age</th>
                <th align="center" style="font-weight: bold;">Nationality</th>
                <th align="center" style="font-weight: bold;">Trip Status</th>
                <th align="center" style="font-weight: bold;">Ticket Status</th>
            </tr> 
            @foreach($passengers as $key => $passenger)
            <tr>
                <td align="center" >{{ $key+1 }}</td>
                <td align="center">{{ $passenger->ticket->reference_number }}</td>
                <td align="center" >{{ $passenger->ticket->group_reference_number }}</td>
                <td align="center" >{{ $passenger->trip_date }}</td>
                <td align="center" >{{ $passenger->first_name }}</td>
                <td align="center" >{{ $passenger->last_name }}</td>
                <td align="center" >{{ $passenger->age }}</td>
                <td align="center">{{ $passenger->nationality }}</td>
                <td align="center" >{{ $passenger->trip->status }}</td>
                <td align="center" >{{ $passenger->ticket->status }}</td>
            </tr> 
            @endforeach
            <tr>
                <th colspan="10" align="right" valign="middle">
                    Pending
                </th>
                <th colspan="1" align="left" valign="middle">
                    {{$statusCounts['pending']}}
                </th>
            </tr>
            <tr>
                <th colspan="10" align="right" valign="middle">
                    Checked-in
                </th>
                <th colspan="1" align="left" valign="middle">
                {{$statusCounts['checked_in']}}
                </th>
            </tr>            
            <tr>
                <th colspan="10" align="right" valign="middle">
                    Boarded
                </th>
                <th colspan="1" align="left" valign="middle">
                {{$statusCounts['boarded']}}
                </th>
            </tr>
            <tr>
                <th colspan="10" align="right" valign="middle">
                    No Show
                </th>
                <th colspan="1" align="left" valign="middle">
                {{$statusCounts['no_show']}}
                </th>
            </tr>
            <tr>
                <th colspan="10" align="right" valign="middle">
                    Cancelled
                </th>
                <th colspan="1" align="left" valign="middle">
                    {{$statusCounts['cancelled']}}
                </th>
            </tr>
            <tr>
                <th colspan="10" align="right" valign="middle" style="font-weight: bold;">
                    TOTAL BOOKINGS
                </th>
                <th colspan="1" align="left" valign="middle" style="font-weight: bold;">
                    {{$total}}
                </th>
            </tr>
    </tbody>
</table>
