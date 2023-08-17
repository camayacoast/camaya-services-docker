<table>
    <thead>
        <tr>
            <th colspan="22" align="center" valign="center" style="font-weight: bold; font-size: 16pt; ">Revenue Report</th>
        </tr>
        <tr>
            <th colspan="22"></th>
        </tr>
        <tr>
            <th rowspan="4" colspan="6" align="center" valign="center" style="font-weight: bold; border: solid;">Promos</th>
            <th rowspan="1" colspan="16" align="center" valign="center" style="font-weight: bold; text-transform: uppercase; border: solid;">{{ \Carbon\Carbon::parse($reports['start_date'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($reports['end_date'])->format('M d, Y') }}</th>

        </tr>
        <tr>
            <th colspan="8" align="center" valign="center" style="font-weight: bold; border: solid;">BPO</th>
            <th colspan="8" align="center" valign="center" style="font-weight: bold; border: solid;">RE</th>
        </tr>
        <tr>
            <th colspan="4" align="center" valign="center" style="font-weight: bold; border: solid;">Weekday</th>
            <th colspan="4" align="center" valign="center" style="font-weight: bold; border: solid;">Weekend</th>
            <th colspan="4" align="center" valign="center" style="font-weight: bold; border: solid;">Weekday</th>
            <th colspan="4" align="center" valign="center" style="font-weight: bold; border: solid;">Weekend</th>
        </tr>
        <tr>
            <th colspan="2" align="center" valign="center" style="font-weight: bold; border: solid;">Pax</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold; border: solid;">Sales</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold; border: solid;">Pax</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold; border: solid;">Sales</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold; border: solid;">Pax</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold; border: solid;">Sales</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold; border: solid;">Pax</th>
            <th colspan="2" align="center" valign="center" style="font-weight: bold; border: solid;">Sales</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reports['data'] as $report)
            <tr>
                <td colspan="6" valign="center" style="border: solid;">{{ $report['promo'] }}</td>
                <td colspan="2" align="center" valign="center" style="border: solid;">{{ $report['bpo']['wd']['pax'] }}</td>
                <td colspan="2" align="center" valign="center" style="border: solid;">{{ $report['bpo']['wd']['sales'] }}</td>
                <td colspan="2" align="center" valign="center" style="border: solid;">{{ $report['bpo']['we']['pax'] }}</td>
                <td colspan="2" align="center" valign="center" style="border: solid;">{{ $report['bpo']['we']['sales'] }}</td>    
                <td colspan="2" align="center" valign="center" style="border: solid;">{{ $report['re']['wd']['pax'] }}</td>
                <td colspan="2" align="center" valign="center" style="border: solid;">{{ $report['re']['wd']['sales'] }}</td>
                <td colspan="2" align="center" valign="center" style="border: solid;">{{ $report['re']['we']['pax'] }}</td>
                <td colspan="2" align="center" valign="center" style="border: solid;">{{ $report['re']['we']['sales'] }}</td>      
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" align="center" style="font-weight: bold; border: solid;">TOTAL</td>
            <td colspan="2" align="center" style="font-weight: bold; border: solid;">{{ collect($reports['data'])->sum('bpo.wd.pax') }}</td>
            <td colspan="2" align="center" style="font-weight: bold; border: solid;">{{ collect($reports['data'])->sum('bpo.wd.sales') }}</td>
            <td colspan="2" align="center" style="font-weight: bold; border: solid;">{{ collect($reports['data'])->sum('bpo.we.pax') }}</td>
            <td colspan="2" align="center" style="font-weight: bold; border: solid;">{{ collect($reports['data'])->sum('bpo.we.sales') }}</td>
            <td colspan="2" align="center" style="font-weight: bold; border: solid;">{{ collect($reports['data'])->sum('re.wd.pax') }}</td>
            <td colspan="2" align="center" style="font-weight: bold; border: solid;">{{ collect($reports['data'])->sum('re.wd.sales') }}</td>
            <td colspan="2" align="center" style="font-weight: bold; border: solid;">{{ collect($reports['data'])->sum('re.we.pax') }}</td>
            <td colspan="2" align="center" style="font-weight: bold; border: solid;">{{ collect($reports['data'])->sum('re.we.sales') }}</td>
        </tr>
    </tfoot>
</table>