<table>
    <tr>
        @foreach($headers as $key => $header)
            <td>{{$header}}</td>
        @endforeach
    </tr>
    @foreach($records as $key => $record)
        <tr>
            @foreach($record as $k => $data)
                <td>{{$data}}</td>
            @endforeach
        </tr>
    @endforeach
</table>