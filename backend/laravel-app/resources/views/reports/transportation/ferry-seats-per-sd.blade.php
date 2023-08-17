<table>
    <thead>
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 16pt;">Ferry Seats Per Sales Director</th>
        </tr>
        <tr>
            <th colspan="6"></th>
        </tr>
        <tr>
            <th align="center" style="font-weight: bold;">Sales Director</th>
            <th align="center" style="font-weight: bold;">Date</th>
            <th align="center" style="font-weight: bold;">Name of Ferry</th>
            <th align="center" style="font-weight: bold;">ETD</th>
            <th align="center" style="font-weight: bold;">ETA</th>
            <th align="center" style="font-weight: bold;">Number of Pax Occupied (Booked by SD)</th>            
        </tr>
    </thead>
    <tbody>
    @foreach($reports as $report)
        <tr>
            <td align="center">{{ $report['sales_director'] }}</td>
            <td align="center">{{ $report['date'] }}</td>
            <td align="center">{{ $report['name_of_ferry'] }}</td>
            <td align="center">{{ $report['etd'] }}</td>
            <td align="center">{{ $report['eta'] }}</td>
            <td align="center">{{ $report['total_pax_booked'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>