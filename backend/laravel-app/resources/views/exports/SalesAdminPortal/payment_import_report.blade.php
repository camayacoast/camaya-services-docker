<table>
    @foreach($reports as $client_number => $attributes)
        <tr>
            <th colspan="9" align="center"><strong>Client Number: {{$client_number}}</strong></th>
        </tr>
        @if($attributes['report_counter'] > 0)
            <tr>
                <th align="center"></th>
                <th align="center">#</th>
                <th align="center">DATE</th>
                <th align="center">AMOUNT</th>
                <th align="center">CLIENT NUMBER</th>
                <th align="center">FIRSTNAME</th>
                <th align="center">LASTNAME</th>
                <th align="center">PAYMENT DESTINATION</th>
            </tr>
        @endif
        @foreach($attributes as $message => $attribute)
            @if($message !== 'reservation_number' && $message !== 'report_counter')
                <tr>
                    <td><strong>{{$message}}</strong></td>
                </tr>
                @if( is_array($attribute) )
                    @foreach($attribute as $k => $data)
                        <tr>
                            <td></td>
                            @foreach($data['data'] as $k => $value)
                                <td align="right">{{$value}}</td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
            @endif
        @endforeach
        @if($attributes['report_counter'] <= 0)
            <tr>
                <td colspan="9" align="center">No Duplicated or Excess Entry Detected</td>
            </tr>  
        @endif
        <tr>
            <td colspan="9" align="center">
                <a href="{{ env('APP_URL') }}/collections/view-account/{{$attributes['reservation_number']}}">
                    {{ env('APP_URL') }}/collections/view-account/{{$attributes['reservation_number']}}
                </a>
            </td>
        </tr>
        <tr>
            <td></td>
        </tr>
    @endforeach
</table>