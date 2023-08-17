<?php

// Divide data to 7 days

$_data = array_chunk($data, 7);

?>
<table>
    <thead>
        <tr>
            <th colspan="35" style="font-size: 24px; font-weight: bold; border: solid 3px black;" align="center">
                PRE-BOOKED SUMMARY REPORT
            </th>
        </tr>
    </thead>
@foreach ($_data as $d)

    <tbody> 
            <tr>
                @foreach ($d as $date => $i)
                    <td style="background-color: #a9d08d; border: solid 1px #000000;" colspan="5" align="center">{{\Carbon\Carbon::parse($i['date'])->format('F d - l')}}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($d as $key => $i)
                    <td style="background-color: #a9d08d" rowspan="2" align="center">GUEST TYPE</td>
                    <td style="background-color: #a9d08d" colspan="2" align="center">DTT</td>
                    <td style="background-color: #a9d08d" colspan="2" align="center">OVERNIGHT</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($d as $key => $i)
                    <td style="background-color: #a9d08d" align="center">FERRY</td>
                    <td style="background-color: #a9d08d" align="center">BY LAND</td>
                    <td style="background-color: #a9d08d" align="center">FERRY</td>
                    <td style="background-color: #a9d08d" align="center">BY LAND</td>
                @endforeach
            </tr>
            
            <tr>
                @foreach ($d as $key => $i)
                    <td>REAL ESTATE</td>
                    <td align="center">{{ $i['DT']['ferry']['real_estate'] }}</td>
                    <td align="center">{{ $i['DT']['by_land']['real_estate'] }}</td>
                    <td align="center">{{ $i['ON']['ferry']['real_estate'] }}</td>
                    <td align="center">{{ $i['ON']['by_land']['real_estate'] }}</td>
                @endforeach
            </tr>

            <tr>
                @foreach ($d as $key => $i)
                    <td>HOMEOWNER</td>
                    <td align="center">{{ $i['DT']['ferry']['homeowner'] }}</td>
                    <td align="center">{{ $i['DT']['by_land']['homeowner'] }}</td>
                    <td align="center">{{ $i['ON']['ferry']['homeowner'] }}</td>
                    <td align="center">{{ $i['ON']['by_land']['homeowner'] }}</td>
                @endforeach
            </tr>

            <tr>
                @foreach ($d as $key => $i)
                    <td>COMMERCIAL</td>
                    <td align="center">{{ $i['DT']['ferry']['commercial'] }}</td>
                    <td align="center">{{ $i['DT']['by_land']['commercial'] }}</td>
                    <td align="center">{{ $i['ON']['ferry']['commercial'] }}</td>
                    <td align="center">{{ $i['ON']['by_land']['commercial'] }}</td>
                @endforeach
            </tr>

            <tr>
                @foreach ($d as $key => $i)
                    <td>EMPLOYEES</td>
                    <td align="center">{{ $i['DT']['ferry']['employees'] }}</td>
                    <td align="center">{{ $i['DT']['by_land']['employees'] }}</td>
                    <td align="center">{{ $i['ON']['ferry']['employees'] }}</td>
                    <td align="center">{{ $i['ON']['by_land']['employees'] }}</td>
                @endforeach
            </tr>

            <tr>
                @foreach ($d as $key => $i)
                    <td>OTHERS</td>
                    <td align="center">{{ $i['DT']['ferry']['others'] }}</td>
                    <td align="center">{{ $i['DT']['by_land']['others'] }}</td>
                    <td align="center">{{ $i['ON']['ferry']['others'] }}</td>
                    <td align="center">{{ $i['ON']['by_land']['others'] }}</td>
                @endforeach
            </tr>

            <tr>
                @foreach ($d as $key => $i)
                    <td>TOTAL</td>
                    <td align="center">{{ $i['DT']['ferry']['total'] }}</td>
                    <td align="center">{{ $i['DT']['by_land']['total'] }}</td>
                    <td align="center">{{ $i['ON']['ferry']['total'] }}</td>
                    <td align="center">{{ $i['ON']['by_land']['total'] }}</td>
                @endforeach
            </tr>

            <tr>
                @foreach ($d as $key => $i)
                    <td>TOTAL PAX</td>
                    <td colspan="4" align="center">
                        {{ $i['DT']['ferry']['total'] +  $i['DT']['by_land']['total'] + $i['ON']['ferry']['total'] + $i['ON']['by_land']['total']}}
                    </td>
                @endforeach
            </tr>
    </tbody>
@endforeach
</table>