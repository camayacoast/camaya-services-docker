<table> 
    
    <thead>

        <tr>
            <th colspan="13" height="50">
                <!-- <img src="images/1bits-logo.png" width="140" /> -->
            </th>
        </tr>
        <tr>
            <th colspan="13" style="font-size: 14pt; font-weight: bold;">Date Range : {{ $startDate }} - {{ $endDate }}</th>
        </tr>
    </thead>
</table>

@foreach($results as $result)

       <table border="2">
            <tbody>
                <tr style="background-color: rgb(169, 208, 141)">
                    <td colspan="4" align="center">{{ $result['formatDate'] }}</td>
                </tr>    
                <tr>
                    <td></td>
                    <td align="center">EST - FTT</td>
                    <td align="center">FTT - EST </td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td align="center">10:00:00 - 12:35:00</td>
                    <td align="center">15:00:00 - 16:30:00 </td>
                    <td></td>
                </tr>

                <tr>
                    <td align="center"><strong>Ticket</strong></td>
                    <td align="center"><strong>Passenger</strong></td>
                    <td align="center"><strong>Passenger</strong></td>
                    <td align="center"><strong>Rate</strong></td>
                </tr>
                <tr>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['regular']['ticket'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['regular']['passenger_count_est'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['regular']['passenger_count_ftt'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['regular']['rate'] }}</strong></td>
                </tr>
                <tr>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['corporate']['ticket'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['corporate']['passenger_count_est'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['corporate']['passenger_count_ftt'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['corporate']['rate'] }}</strong></td>
                </tr>
                <tr>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['discounted']['ticket'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['discounted']['passenger_count_est'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['discounted']['passenger_count_ftt'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['discounted']['rate'] }}</strong></td>
                </tr>

                <tr>
                    <td align="center"><strong>Total</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['total']['est_passenger_total'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['total']['ftt_passenger_total'] }}</strong></td>
                    <td align="center"><strong>{{ $result['tickets']['ticketData']['total']['total_rate'] }}</strong></td>
                </tr>
            </tbody>
        </table>
       
@endforeach
