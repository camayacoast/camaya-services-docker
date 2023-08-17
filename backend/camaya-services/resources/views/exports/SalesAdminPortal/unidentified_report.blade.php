<table>
    <tr>
        @foreach( $headers as $header )
            <th>{{$header}}</th>
        @endforeach
    </tr>
    @if( !empty($data) )
        @foreach( $data as $key => $records )
            <tr>
                @foreach( $records as $column => $record )
                    <th>{{$record}}</th>
                @endforeach
            </tr>
        @endforeach
    @endif
</table>