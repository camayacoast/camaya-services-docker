<table>
    <!-- <thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </thead>  -->
    <tbody>                                                   
        <tr>
            <th valign="center">Client Number</th>
            <th valign="center">Phase</th>
            <th valign="center">Block</th>
            <th valign="center">Lot</th>
            <th valign="center">Area</th>
            
            <th valign="center">Lname</th>
            <th valign="center">Fname</th>

            <th valign="center">PC</th>
            <th valign="center">SM</th>
            <th valign="center">SD</th>

            <th valign="center">RF Date</th>
            <th valign="center">Due Date</th>

            <th valign="center">Terms</th>
            <th valign="center">Discount (%)</th>

            <th valign="center">Projected First DPs</th>
            <th valign="center">Projected Bal</th>

            <th valign="center">Balance (Yrs)</th>
            <th valign="center">Projected MAs</th>
            <th valign="center">Start of MAs</th>

            <th valign="center">DP Date Paid</th>
            
            <th valign="center">NSP</th>
            <th valign="center">TSP</th>

            <th valign="center">Promo</th>

            <th valign="center">Status</th>
        </tr>
        @foreach($reservations as $key => $reservation)
            <tr>
                <td>{{$reservation->client_number}}</td>
                <td>{{$reservation->subdivision}}</td>
                <td>{{$reservation->block}}</td>
                <td>{{$reservation->lot}}</td>
                <td>{{$reservation->area}}</td>

                <td>{{$reservation->client->last_name ?? ''}}</td>
                <td>{{$reservation->client->first_name ?? ''}}</td>

                <td>
                    @if ($reservation->agent)
                        {{$reservation->agent->first_name}} {{$reservation->agent->last_name}}
                    @endif
                </td>

                <td>
                    @if (isset($reservation->sales_manager_id))
                        {{$reservation->sales_manager->first_name}} {{$reservation->sales_manager->last_name}}
                    @else
                        @if (isset($reservation->sales_director_id))
                            {{$reservation->sales_director->first_name}} {{$reservation->sales_director->last_name}}
                        @endif
                    @endif
                </td>
                <td>
                    @if (isset($reservation->sales_director_id))
                        {{$reservation->sales_director->first_name}} {{$reservation->sales_director->last_name}}
                    @endif
                </td>

                <td>
                    {{date('d-M-Y', strtotime($reservation->reservation_fee_date))}}
                </td>
                <td><!-- Due Date -->-</td>

                <td>
                    @if ($reservation->payment_terms_type == 'cash')
                        Cash
                    @endif
                    @if ($reservation->payment_terms_type == 'in_house')
                        In-House
                    @endif
                </td>
                <td>{{number_format($reservation->discount_percentage, 1)}}%</td>

                <td>
                    <!-- {{$reservation->reservation_fee_amount ?  $reservation->downpayment_amount_less_RF : $reservation->downpayment_amount}} -->
                    @if ($reservation->payment_terms_type == 'in_house')
                        @if ($reservation->reservation_fee_amount)
                            @if ($reservation->split_downpayment)
                                {{number_format($reservation->downpayment_amount_less_RF / ($reservation->number_of_downpayment_splits <= 0 || is_null($reservation->number_of_downpayment_splits) ? 1 : $reservation->number_of_downpayment_splits), 2)}}
                            @else
                                {{number_format($reservation->downpayment_amount_less_RF, 2)}}
                            @endif
                        @else
                            @if ($reservation->split_downpayment)
                                {{number_format($reservation->downpayment_amount / ($reservation->number_of_downpayment_splits <= 0 || is_null($reservation->number_of_downpayment_splits) ? 1 : $reservation->number_of_downpayment_splits), 2)}}
                            @else
                                {{number_format($reservation->downpayment_amount, 2)}}
                            @endif
                        @endif
                    @endif
                </td>
                <td>
                    <!-- Projected Bal -->
                    @if ($reservation->payment_terms_type == 'cash')
                        {{number_format($reservation->retention_fee, 2)}}
                    @else
                        {{number_format($reservation->total_balance_in_house, 2)}}
                    @endif
                </td>

                <td>
                    @if ($reservation->payment_terms_type == 'in_house')    
                        {{$reservation->number_of_years}}yrs
                    @endif
                </td>
                <td>{{number_format($reservation->monthly_amortization, 2)}}</td>
                <td>
                    <!-- Start of MAs -->
                    @if ($reservation->payment_terms_type == 'in_house')
                        {{date('d-M-Y', strtotime($reservation->monthly_amortization_due_date))}}
                    @endif
                </td>

                <td>
                    <!-- DP Date Paid -->
                    -
                </td>
                
                <td>
                    {{$reservation->with_twelve_percent_vat ? number_format($reservation->net_selling_price_with_vat, 2) : number_format($reservation->net_selling_price, 2)}}
                </td>
                <td>{{number_format($reservation->total_selling_price, 2)}}</td>

                <td>
                    @foreach($reservation->promos as $promo)
                        {{$promo['promo_type']}}&nbsp;
                    @endforeach
                </td>

                <td>{{$reservation->status}}</td>
            </tr>
        @endforeach
    </tbody>
</table>
