<table>
    <thead>
        <tr>
            <th colspan="10" align="center" valign="center" style="font-weight: bold; font-size: 16pt; ">SDMB Booking Consumption Report (Via Land &amp; Ferry)</th>
        </tr>
        <tr>
            <th colspan="10"></th>
        </tr>
        <tr>
            <th colspan="4" valign="center" style="font-weight: bold;">Sales Director</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold;">Land</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold;">Ferry</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold;">Total</th>
        </tr>
    </thead>
    <tbody>
    @foreach($reports as $report)
        <tr>
            <td colspan="4" >{{ $report['sd_name'] }}</td>
            <td colspan="2" align="center">{{ $report['land_total_sales'] }}</td>
            <td colspan="2" align="center">{{ $report['ferry_total_sales'] }}</td>
            <td colspan="2" align="center">{{ $report['land_total_sales'] + $report['ferry_total_sales'] }} </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td rowspan="2" colspan="4" valign="center" style="font-weight: bold;">GRAND TOTAL</td>
            <td rowspan="2" colspan="2" valign="center" align="center" style="font-weight: bold;">{{ collect($reports)->sum('land_total_sales') }}</td>
            <td rowspan="2" colspan="2" valign="center" align="center" style="font-weight: bold;">{{ collect($reports)->sum('ferry_total_sales') }}</td>
            <td rowspan="2" colspan="2" valign="center" align="center" style="font-weight: bold;">{{ collect($reports)->sum('land_total_sales') + collect($reports)->sum('ferry_total_sales') }}</td>
        </tr>
    </tfoot>
</table>