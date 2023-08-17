<table>
    <thead>
        <tr>
            <th>Inventory Status Report</th>
        </tr>
        <tr>
            <th></th>
        </tr>
    </thead> 
    <tbody>                                                   
        <tr>
            <th valign="center" style="background-color: #99cbff;">Phase</th>

            @if($property_type == 'lot')
                <th valign="center" style="background-color: #99cbff;">Subd</th>
            @else
                <th valign="center" style="background-color: #99cbff;">Proj</th>
            @endif
            
            @if($property_type == 'lot')
                <th valign="center" style="background-color: #99cbff;">Blk</th>
                <th valign="center" style="background-color: #99cbff;">Lot</th>
            @else
                <th valign="center" style="background-color: #99cbff;">Bldg</th>
                <th valign="center" style="background-color: #99cbff;">Unit</th>
            @endif

            <th valign="center" style="background-color: #99cbff;">Client</th>
            <th valign="center" style="background-color: #99cbff;">Client Name</th>
            <th valign="center" style="background-color: #99cbff;">Status</th>
            <th valign="center" style="background-color: #99cbff;">Status2</th>
            <th valign="center" style="background-color: #99cbff;">Color</th>
            <th valign="center" style="background-color: #99cbff;">Remarks</th>
            <th valign="center" style="background-color: #99cbff;">Area</th>
            <th valign="center" style="background-color: #99cbff;">Type</th>
            <th valign="center" style="background-color: #99cbff;">Price M2</th>
            <th valign="center" style="background-color: #99cbff;">TSP</th>
        </tr>
        @foreach($lots as $key => $lot)
            <tr>
                <td>{{$lot['phase']}}</td>
                <td>{{$lot['subdivision']}}</td>
                <td>{{$lot['block']}}</td>
                <td>{{$lot['lot']}}</td>
                <td>{{$lot['client_number']}}</td>
                <td>{{$lot['client_name']}}</td>
                <td>{{$lot['status']}}</td>
                <td>{{$lot['status2']}}</td>
                <td>{{$lot['color']}}</td>
                <td>{{$lot['remarks']}}</td>
                <td>{{$lot['area']}}</td>
                <td>{{$lot['type']}}</td>
                <td>{{$lot['price_per_sqm']}}</td>

                <td>{{number_format(round($lot['tsp']), 2)}}</td>
            </tr>
        @endforeach
    </tbody>
</table>
