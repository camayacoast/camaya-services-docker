<table>
    <thead>
        <tr>
            <th colspan="13" height="50">
                <!-- <img src="images/1bits-logo.png" width="140" /> -->
            </th>
        </tr>
        <tr>
            <th colspan="13" align="center" style="font-size: 16pt; font-weight: bold; border: solid 3px black;">Sales Report</th>
        </tr>
        <tr>
            <th colspan="13"><!--spacer--></th>
        </tr>
        <tr>
            <th colspan="13" align="center" style="font-size: 14pt; font-weight: bold;">{{$start_date}} - {{$end_date}}</th>
        </tr>
        
    </thead>    
    <tbody>                
    <tr>
                                                             
            <tr>
                <th align="center" style="font-weight: bold;">#</th>
                <th align="center" style="font-weight: bold;">Transaction ID</th>
                <th align="center" style="font-weight: bold;">Lead Name</th>
                <th align="center" style="font-weight: bold;">Email</th>
                <th align="center" style="font-weight: bold;">Contact Number</th>
                <th align="center" style="font-weight: bold;">Date of Departure</th>
                <th align="center" style="font-weight: bold;">Route</th>
                <th align="center" style="font-weight: bold;">Time</th>
                <th align="center" style="font-weight: bold;">Pax</th>
                <th align="center" style="font-weight: bold;">Ticket Type</th>
                <th align="center" style="font-weight: bold;">O.R. #</th>
                <th align="center" style="font-weight: bold;">Promo</th>
                <th align="center" style="font-weight: bold;">Cash</th>
                <th align="center" style="font-weight: bold;">Online</th>
                <th align="center" style="font-weight: bold;">Remarks</th>
            </tr> 
            @php
                $totalCash = 0;
                $totalOnline = 0;
            @endphp
                @foreach($bookings as $key => $booking)
                <tr>
                    <td align="center" >{{ $key+1 }}</td>
                    <td align="center">{{ $booking->group_reference_number }}</td>
                    <td align="center" >{{ $booking->first_passenger_name }}</td>
                    <td align="center" >{{ $booking->email }}</td>
                    <td align="center" >{{ $booking->contact_number }}</td>
                    <td align="center" >{{ $booking->trip_date }}</td>
                    <td align="center" >{{ $booking->trip_details }}</td>
                    <td align="center" >{{ $booking->start_time }}</td>
                    <td align="center" >{{ $booking->total_ticket_count }}</td>
                    <td align="center" >{{ $booking->ticket_type }}</td>
                    <td align="center" >{{ $booking->payment_reference_number }}</td>
                    <td align="center" > {{ strpos($booking->discount_id, 'Promo-') === 0 ? $booking->discount_id : '-' }}</td>
                    <td align="center" >
                    @if($booking->payment_provider === 'cashier')
                        {{ $booking->total_booking_amount }}
                        @php $totalCash += $booking->total_booking_amount @endphp
                    @else 
                        -
                    @endif
                    </td>
                    <td align="center" >
                    @if($booking->payment_provider !== 'cashier')
                        {{ $booking->total_booking_amount }}
                        @php $totalOnline += $booking->total_booking_amount @endphp
                    @else 
                        -
                    @endif
                    </td>
                    <td align="center" >{{ $booking->remarks }}</td>
                    
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
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td align="center" style="font-weight: bold;">Total Cash:  <h2>{{ $totalCash }}</h2></td>
                <td align="center" style="font-weight: bold;">Total Online:  <h2>{{ $totalOnline }}</h2></td>
                <td></td>
            </tr>
    </tbody>
</table>
