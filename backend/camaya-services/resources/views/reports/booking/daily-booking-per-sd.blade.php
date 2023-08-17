<table>
    <thead>
        <tr>
            <th colspan="11" style="font-weight: bold; font-size: 16pt;">Daily Bookings Per Sales Director</th>
        </tr>
        <tr>
            <th colspan="11"></th>
        </tr>
        <tr>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">Sales Director</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">No. of Guest in Land Bookings</th>
            <th rowspan="2" align="center" valign="center" style="font-weight: bold;">No. of Guest in Ferry Bookings</th>
            <th colspan="4" align="center" style="font-weight: bold;">Guest Status (LAND)</th>
            <th colspan="4" align="center" style="font-weight: bold;">Guest Status (FERRY)</th>
        </tr>
        <tr>
            <th align="center" style="font-weight: bold;">Arriving</th>
            <th align="center" style="font-weight: bold;">Arrived</th>
            <th align="center" style="font-weight: bold;">Cancelled</th>
            <th align="center" style="font-weight: bold;">No Show</th>
            <th align="center" style="font-weight: bold;">Arriving</th>
            <th align="center" style="font-weight: bold;">Arrived</th>
            <th align="center" style="font-weight: bold;">Cancelled</th>
            <th align="center" style="font-weight: bold;">No Show</th>
        </tr>
    </thead>
    <tbody>
    @foreach($reports as $report)
        <tr>
            <td align="center">{{ $report['sd_name'] }}</td>
            <td align="center">{{ $report['counter_land_total_bookings'] }}</td>
            <td align="center">{{ $report['counter_ferry_total_bookings'] }}</td>
            <td align="center">{{ $report['counter_land_arriving'] }}</td>
            <td align="center">{{ $report['counter_land_arrived'] }}</td>
            <td align="center">{{ $report['counter_land_cancelled'] }}</td>      
            <td align="center">{{ $report['counter_land_no_show'] }}</td>
            <td align="center">{{ $report['counter_ferry_arriving'] }}</td>
            <td align="center">{{ $report['counter_ferry_arrived'] }}</td>
            <td align="center">{{ $report['counter_ferry_cancelled'] }}</td>
            <td align="center">{{ $report['counter_ferry_no_show'] }}</td>            
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td align="center" style="font-weight: bold;">TOTAL</td>
            <td align="center" style="font-weight: bold;">{{ collect($reports)->sum('counter_land_total_bookings') }}</td>
            <td align="center" style="font-weight: bold;">{{ collect($reports)->sum('counter_ferry_total_bookings') }}</td>
            <td align="center" style="font-weight: bold;">{{ collect($reports)->sum('counter_land_arriving') }}</td>
            <td align="center" style="font-weight: bold;">{{ collect($reports)->sum('counter_land_arrived') }}</td>
            <td align="center" style="font-weight: bold;">{{ collect($reports)->sum('counter_land_cancelled') }}</td>
            <td align="center" style="font-weight: bold;">{{ collect($reports)->sum('counter_land_no_show') }}</td>
            <td align="center" style="font-weight: bold;">{{ collect($reports)->sum('counter_ferry_arriving') }}</td>
            <td align="center" style="font-weight: bold;">{{ collect($reports)->sum('counter_ferry_arrived') }}</td>
            <td align="center" style="font-weight: bold;">{{ collect($reports)->sum('counter_ferry_cancelled') }}</td>
            <td align="center" style="font-weight: bold;">{{ collect($reports)->sum('counter_ferry_no_show') }}</td>
        </tr>
    </tfoot>
</table>