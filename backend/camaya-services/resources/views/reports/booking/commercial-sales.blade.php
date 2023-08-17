<table>
    <thead>
        <tr>
            <th colspan="23" style="font-weight: bold; font-size: 16pt; ">Commercial Sales</th>
        </tr>
        <tr>
            <th colspan="23"></th>
        </tr>
        <tr>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Date of Visit</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Date of Booking</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Primary Guest Name</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">DT/ON</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">New Booking Code</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Market Segmentation</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Package Promo</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Promo Availed</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold;">Pax</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Check-in</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Check-out</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Number of Nights</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Hotel</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Number of Rooms</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Type of Rooms</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Meal Arrangement</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Mode of Transportation</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Amount</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Mode of Payment</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Source of Sale</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Contact Number</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Email Address</th>
        </tr>
        <tr>
            <th align="center" valign="center" style="font-weight: bold;">Adults</th>
            <th align="center" valign="center" style="font-weight: bold;">Kids</th>            
        </tr>
    </thead>
    <tbody>
    @foreach($reports as $report)
        <tr>
            <td align="center">{{ $report['date'] }}</td>
            <td align="center">{{ $report['date_created'] }}</td>
            <td align="center">{{ $report['customer']['first_name'] }} {{ $report['customer']['last_name'] }}</td>
            <td align="center">{{ $report['type'] }}</td>
            <td align="center">{{ $report['reference_number'] }}</td>
            <td align="center">{{ $report['market_segmentation'] }}</td>
            <td align="center">{{ collect($report['tags'])->implode('name', ', ') }}</td>
            <td align="center">
                @if($report['label'])
                    {{ $report['label'] }}
                @else
                    {{ $report['remarks'] }}
                @endif
            </td>
            <td align="center">{{ $report['adult_pax'] }}</td>
            <td align="center">{{ $report['kid_pax'] }}</td>
            @if($report['type'] === 'ON')
                <td align="center">{{ $report['check_in'] }}</td>
                <td align="center">{{ $report['check_out'] }}</td>
                <td align="center">{{ $report['no_of_nights'] }}</td>
                <td align="center">{{ $report['hotel'] }}</td>
                <td align="center">{{ $report['no_of_rooms'] }}</td>
                <td align="center">{{ $report['room_type'] }}</td>
            @else
                <td align="center" style="background-color: #E7E6E6"></td>
                <td align="center" style="background-color: #E7E6E6"></td>
                <td align="center" style="background-color: #E7E6E6"></td>
                <td align="center" style="background-color: #E7E6E6"></td>
                <td align="center" style="background-color: #E7E6E6"></td>
                <td align="center" style="background-color: #E7E6E6"></td>                
            @endif                        
            <td align="center"></td>
            <td align="center">{{ $report['mode_of_transportation'] }}</td>
            <td align="center">{{ $report['amount'] }}</td>
            <td align="center">{{ $report['mode_of_payment'] }}</td>
            <td align="center">{{ $report['source'] }}</td>
            <td align="center">{{ $report['customer']['contact_number'] }}</td>
            <td align="center">{{ $report['customer']['email'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>