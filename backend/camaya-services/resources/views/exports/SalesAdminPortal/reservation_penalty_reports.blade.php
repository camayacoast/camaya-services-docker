<table>
    <tr>
        <th colspan="8">{{$title}}</th>
    </tr>
    @foreach($summaries as $key => $summary)
        <tr>
            @foreach($summary as $k => $detail)
                <td>{{$detail}}</td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        @foreach($table_header as $key => $label)
            <td>{{$label}}</td>
        @endforeach
    </tr>
    
    @foreach($penalty_data as $key => $penalty)

        <tr>
            <td>{{$penalty['due_date']}}</td>
            <td>{{$penalty['number']}}</td>
            <td>{{$penalty['amount']}}</td>
            <td>{{$penalty['penalty_amount']}}</td>
            <td>{{$penalty['balance']}}</td>
            <td>{{$penalty['discount']}}</td>
            <td>{{$penalty['actual_payment']}}</td>
            <td>{{$penalty['status']}}</td>
        </tr>

    @endforeach
</table>
