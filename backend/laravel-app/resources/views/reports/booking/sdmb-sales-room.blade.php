<table>
    <thead>
        <tr>
            <th colspan="24" align="center" valign="center" style="font-weight: bold; font-size: 16pt; ">SDMB Sales Accommodation Report</th>
        </tr>
        <tr>
            <th colspan="24"></th>
        </tr>
        <tr>
            <th colspan="3" valign="center" style="font-weight: bold;">Sales Director</th>
            <th colspan="3" valign="center" style="font-weight: bold;">Agent</th>
            <th colspan="3" valign="center" style="font-weight: bold;">Guest</th>
            <th colspan="2" valign="center" style="font-weight: bold;">Booking Code</th>
            <th colspan="2" valign="center" style="font-weight: bold;">Check-in</th>
            <th colspan="2" valign="center" style="font-weight: bold;">Check-out</th>
            <th colspan="1" valign="center" style="font-weight: bold;">No. of Nights</th>
            <th colspan="2" valign="center" style="font-weight: bold;">Hotel</th>
            <th colspan="1" valign="center" style="font-weight: bold;">No. of Rooms</th>
            <th colspan="2" valign="center" style="font-weight: bold;">Room Type</th>
            <th colspan="2" valign="center" style="font-weight: bold;">Rate</th>
            <th colspan="1" valign="center" style="font-weight: bold;">Extra Pax</th>
            <th colspan="2" valign="center" style="font-weight: bold;">Extra Pax Fee</th>
            <th colspan="2" valign="center" style="font-weight: bold;">Total</th>
            <th colspan="4" valign="center" style="font-weight: bold;">Remarks</th>
        </tr>
    </thead>
    <tbody>
    @foreach($reports as $report)
        <tr>
            <td colspan="3"> {{ $report['sales_director']['first_name'] }} {{ $report['sales_director']['last_name'] }}</td>

            <td colspan="3"> {{ $report['agent']['first_name'] }} {{ $report['agent']['last_name'] }} </td>

            <td colspan="3"> {{ $report['customer']['first_name'] }} {{ $report['customer']['last_name'] }} </td>

            <td colspan="2"> {{ $report['reference_number'] }} </td>

            <td colspan="2"> {{ date('d-M-Y', strtotime($report['check_in'])) }} </td>
            <td colspan="2"> {{ date('d-M-Y', strtotime($report['check_out'])) }} </td>

            <td colspan="1" align="left"> {{ $report['no_of_nights'] }} </td>

            <td colspan="2"> {{ $report['hotel'] }} </td>
            
            <td colspan="1" align="left"> {{ $report['no_of_rooms'] }} </td>

            {{-- Room Type --}}
            <td colspan="2"> {{ collect(collect($report['room_reservations_no_filter'])->unique('room_type.code')->values()->all())->map( function ($item) { return $item['room_type']['code']; })->join(", ") }}</td>

            {{-- Rate --}}
            <td colspan="2" align="left"> {{ ($report['inclusions_grand_total'] ?? 0) + ($report['inclusions_grand_total_on_package'] ?? 0) }} </td>

            {{-- Extra Pax --}}
            <td colspan="1" align="center"> 
                {{ $report['extra_pax'] }} 
            </td>
            
            {{-- Extra Pax Fee --}}
            <td colspan="2" align="left">
                {{-- {{ (collect($report['inclusions'])->firstWhere('code', 'EXTRAPAX') !== null) ? collect($report['inclusions'])->firstWhere('code', 'EXTRAPAX')['quantity'] * collect($report['inclusions'])->firstWhere('code', 'EXTRAPAX')['price'] : 0 }} --}}
                {{ $report['extra_pax_fee'] }} 
            </td>

            {{-- Total --}}
            <td colspan="2" align="left"> {{ $report['grand_total'] }} </td>

            <td colspan="4"> {{ $report['remarks'] }} </td>

        </tr>
    @endforeach
    </tbody>
</table>