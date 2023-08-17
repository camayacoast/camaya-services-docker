<table>
    <tr>
        <td colspan="7">{{$client}}</td>
        <td></td>
        <td></td>
        <td>SUBD</td>
        <td>BLOCK</td>
        <td>LOT</td>
        <td>PROPERTY CONSULTANT</td>
        <td>{{$agent}}</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>{{$subdivision}}</td>
        <td>{{$block}}</td>
        <td>{{$lot}}</td>
        <td>SALES MANAGER</td>
        <td>{{$sales_manager}}</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>SALES DIRECTOR</td>
        <td>{{$sales_director}}</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>SENIOR SALES DIRECTOR</td>
        <td>{{$sales_director}}</td>
    </tr>
    <tr>
        <td></td>
        <td>DUE DATE</td>
        <td>AMOUNT</td>
        <td>DATE PAID</td>
        <td>AMT. PAID</td>
        <td>PR #</td>
        <td>OR #</td>
        <td>ACCT #</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    @foreach( $payment_details as $key => $details )
        <tr>
            @foreach( $details as $k => $value )
                <td>{{$value}}</td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        <td>AMORTIZATION</td>
        <td>DATE DUE</td>
        <td>AMOUNT</td>
        <td>DATE PAID</td>
        <td>AMT. PAID</td>
        <td>PENALTY PAID</td>
        <td>PR #</td>
        <td>OR #</td>
        <td>ACCT #</td>
        <td>PRINCIPAL</td>
        <td>INTEREST</td>
        <td>BALANCE</td>
        <td>REMARKS</td>
        <td>DATE/TIME</td>
    </tr>
    @foreach( $schedules as $key => $details )
        <tr>
            @foreach( $details as $k => $value )
                <td>{{$value}}</td>
            @endforeach
        </tr>
    @endforeach
</table>