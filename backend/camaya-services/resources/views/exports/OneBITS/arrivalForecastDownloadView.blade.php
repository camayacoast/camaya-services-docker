<table> 
    
    <thead>

        <tr>
            <th colspan="13" height="50">
                <!-- <img src="images/1bits-logo.png" width="140" /> -->
            </th>
        </tr>

        <tr>
            <th colspan="13">Pending:  {{$statusCounts['pending']}}</th>            
        </tr>

        <tr>
            <th colspan="13">Checked-in:  {{$statusCounts['checked_in']}}</th>            
        </tr>

        <tr>
            <th colspan="13">Boarded:  {{$statusCounts['boarded']}}</th>            
        </tr>
        <tr>
            <th colspan="13">No Show:  {{$statusCounts['no_show']}}</th>            
        </tr>
        <tr>
            <th colspan="13">Cancelled:  {{$statusCounts['cancelled']}}</th>            
        </tr>
        <tr>
            <th colspan="13"><!--spacer--></th>
        </tr>
        <tr>
            <th colspan="13" style="font-size: 14pt; font-weight: bold;">Total Passengers: {{$total - ($statusCounts['no_show'] + $statusCounts['cancelled'])}}</th>
        </tr>
    </thead>
    <tbody>                
                                                             
            <tr>
                <th align="center" style="font-weight: bold;">#</th>
                <th align="center" style="font-weight: bold;">Ticket Ref. no.</th>
                <th align="center" style="font-weight: bold;">Booking Ref. no.</th>
                <th align="center" style="font-weight: bold;">Trip</th>
                <th align="center" style="font-weight: bold;">Firstname</th>
                <th align="center" style="font-weight: bold;">Lastname</th>
                <th align="center" style="font-weight: bold;">Age</th>
                <th align="center" style="font-weight: bold;">Nationality</th>
                <th align="center" style="font-weight: bold;">Contact Number</th>
                <th align="center" style="font-weight: bold;">Email</th>
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
                <td align="center">{{ $passenger->ticket->contact_number }}</td>
                <td align="center">{{ $passenger->ticket->email }}</td>
                <td align="center" >{{ $passenger->trip->status }}</td>
                <td align="center" >{{ $passenger->ticket->status }}</td>
            </tr> 
            @endforeach
           
    </tbody>
</table>
