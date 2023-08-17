<style>
.page-break {
    page-break-after: always;
}

.page-break-before {
    page-break-before: always;
}

.table-noborder {
    /* margin: 0 !important; */
    /* padding: 0 !important; */
}

.ra .table-noborder td {
    border: none !important;
    margin: 0 !important;
    padding: 0 !important;
}

.ra .table{
    border-collapse: separate;
    border-spacing: 5px;
    font-family: Arial, sans-serif;
    font-size: 10px;
}

.ra .table-min {
    border-collapse: separate;
    border-spacing: 0px;
    font-family: Arial, sans-serif;
    font-size: 10px;
}

.ra .table-min td {
    border-left: solid 1px #111;
    border-top: solid 1px #111;
    padding: 3px;
}

.ra .table-min td.label {
    width: 200px;
    min-width: 0%;
}

.ra .table-min td:last-child {
    border-right: solid 1px #111;
}

.ra .table-min tr:last-child > td {
    border-bottom: solid 1px #111;
}

.ra .table thead tr th {
/* font-size: 22px;
text-align: left;
letter-spacing: .03em;
font-weight: bold;
margin: 10px 0 5px;
line-height: .75em; */
/*padding: 25px 25px;*/
}
.ra .table tbody tr td {
/* border: 1px solid black; */
}

.ra .blue-bg {
    background: rgb(1,126,197);
    color: white;
}

.italic {
    font-style: italic;
}

.w-100 {
    width: 100%;
}

.m-0 {
    margin: 0;
}

.b-none {
    border: none;
}

.v-top {
    vertical-align: top;
}

.inner-label {
    font-size: 7px;
    /* margin-bottom: 8px; */
}

.inner-label span + span {
    margin-left: 18%;
}

.ra .list {
    margin: 0px 0;
}

.ra .list li {
    list-style: circle;
    float: left;
    font-size: 30px;
    margin-right: 50px;
    line-height: 20px;
}

.ra .list li span {
    font-size: 10px;
    vertical-align: bottom;
}

.ra .list:after {
    clear: both;
}


.border-bottom {
    border-bottom: solid 1px #111;
}

.ra .terms li + li {
    margin-bottom: 5px;
}
</style>

<img src="{{'images/RA-Header.png'}}" align="center" width="100%"/>

<div class="ra" style="padding: 0px 10px; position: relative; width: 100%;">
    
    <div style="padding: 10px;">
        <small class="italic" style="font-size: 10px; margin-left: 5px;">Please print legibly</small>
        <h6 class="m-0" style="padding-left: 5px; padding-bottom: 5px;">BUYER'S NAME</h6>
        <div style="padding: 5px; border: solid 1px #111;">
            <table class="table-noborder w-100 m-0" style="border-collapse: separate; border-spacing: 0; 0; font-size: 7px;">
                <tr>
                    <td class="v-top">Last Name<br/>
                        <span style="font-size: 10px;">{{$data->client->last_name}}</span>
                    </td>
                    <td class="v-top">First Name<br/>
                        <span style="font-size: 10px;">{{$data->client->first_name}}</span></td>
                    <td class="v-top">Middle Name<br/>
                        <span style="font-size: 10px;">{{$data->client->middle_name}}</span></td>
                    <td class="v-top">Extn (Jr., Sr., III)<br/>
                        <span style="font-size: 10px;">{{$data->client->information->extension ?? ''}}</span></td>
                </tr>
            </table>
        </div>

        <div>
            <h6 class="m-0" style="padding-left: 5px; padding-bottom: 0px; margin-top: 7px; float: left;">AVAILABLE PROPERTY</h6>
            <ul class="gender" style="margin: 0; margin-left: 150px; margin-top: -5px;">
                <li style="{{ $data->property_type == 'Lot' ? 'list-style: disc !important;' : null}}"><span>Lot</span></li>
                <li style="{{ $data->property_type == 'Condo' ? 'list-style: disc !important;' : null}}"><span>Condo</span></li>
            </ul>
        </div>
        <div style="clear: both;"></div>
    </div>

    <table class="table w-100 m-0" style="width: 100%;">
        <tr>
            <td class="blue-bg" style="height: 16px; text-align: center;">1</td>
            <td style="padding: 0;" rowspan="2" class="b-none v-top">
                <table class="table-min m-0 w-100" style="padding: 0px;">
                    <tr>
                        <td class="blue-bg" style="width: 140px;">Source</td>
                        <td class="label" style="width: 50px;">Phase</td>
                        <td colspan="3">{{$data->subdivision}}</td>
                        <td class="label" style="width: 90px;">Approximate Area</td>
                        <td>{{$data->area}}</td>
                    </tr>
                    <tr>
                        <td>{{$data->source}}</td>
                        <td class="label" style="width: 50px;">Block</td>
                        <td>{{$data->block}}</td>
                        <td class="label" style="width: 50px;">Lot</td>
                        <td>{{$data->lot}}</td>
                        <td class="label" style="width: 90px;">Total Selling Price</td>
                        <td>{{number_format($data->total_selling_price, 2)}}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2"></td>
        </tr>

        <tr>
            <td class="v-top" style="height: 16px; text-align: center;">
                <div class="blue-bg w-100" style="padding: 5px 0px;">2</div>
            </td>
            <td class="b-none" style="text-align: center;">
                <table class="table-min m-0 w-100" style="padding: 0px;">
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg border-bottom" style="width: 120px;">Reservation Fee Date</td>
                        <td class="border-bottom">{{date('M d, Y', strtotime($data->reservation_fee_date))}}</td>
                        <td class="border-bottom">Php</td>
                        <td colspan="3" class="border-bottom">{{number_format($data->reservation_fee_amount, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;" colspan="7">
                        <div style="border: solid 2px #111; width: 30px; height: 10px; float: left; {{$data->payment_terms_type == 'cash' ? 'background: #111;':''}}"></div>
                        <strong style="margin-left: 10px;">CASH</strong> Full payment will start 30 days from Reservation Date</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Total Selling Price</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'cash' ? $data->total_selling_price : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Less: Discount ({{number_format($data->payment_terms_type == 'cash' ? $data->discount_percentage : null, 1)}}%)</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'cash' ? $data->discount_amount : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Net Selling Price w/o VAT</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'cash' ? $data->net_selling_price : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">12% VAT</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'cash' ? $data->twelve_percent_vat : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Net Selling Price with VAT</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'cash' && $data->with_twelve_percent_vat ? $data->net_selling_price_with_vat : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Less: Reservation Fee</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'cash' ? $data->reservation_fee_amount : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">5% Retention Fee</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'cash' && $data->with_five_percent_retention_fee ? $data->retention_fee : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td class="border-bottom" style="border: none;"></td>
                        <td class="blue-bg border-bottom" colspan="2"><strong>TOTAL AMOUNT PAYABLE</strong></td>
                        <td class="border-bottom">Php</td>
                        <td class="border-bottom" colspan="3">{{number_format($data->payment_terms_type == 'cash' ? $data->total_amount_payable : null, 2)}}</td>
                    </tr>

                    <tr>
                        <td style="border: none;" colspan="7">
                        <div style="border: solid 2px #111; width: 30px; height: 10px; float: left;  {{$data->payment_terms_type == 'in_house' ? 'background: #111;':''}}"></div>
                        <strong style="margin-left: 10px;">IN HOUSE ASSISTED FINANCING</strong> DP will start within 30 days from Reservation Date. Amortization to start 30 days after Full DP</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Total Selling Price</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'in_house' ? $data->total_selling_price : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Less: Discount ({{number_format($data->payment_terms_type == 'in_house' ? $data->discount_percentage : null, 1)}}%)</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'in_house' ? $data->discount_amount : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Net Selling Price w/o VAT</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'in_house' && !$data->with_twelve_percent_vat ? $data->net_selling_price : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">12% VAT</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'in_house' && $data->with_twelve_percent_vat ? $data->twelve_percent_vat : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Net Selling Price with VAT</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'in_house' && $data->with_twelve_percent_vat ? $data->net_selling_price_with_vat : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2" style="position: relative; overflow: hidden;">
                            <div style="position: absolute; padding: 5px; top: 0; left: 0; border-right: solid 1px #111; height: 100%; background: white; color: #000;">% &nbsp;&nbsp;</div>
                            <div style="margin-left: 25px;">Down Payment</div></td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'in_house' ? ($data->reservation_fee_amount ? $data->downpayment_amount_less_RF : $data->downpayment_amount) : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Less: Reservation Fee</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'in_house' ? $data->reservation_fee_amount : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2" style="position: relative; overflow: hidden;">Down Payment</td>
                        <td>Php</td>
                        <td>{{number_format($data->payment_terms_type == 'in_house' ? ($data->reservation_fee_amount ? $data->downpayment_amount_less_RF : $data->downpayment_amount) : null, 2)}}</td>
                        <td class="blue-bg">Due Date</td>
                        <td>{{$data->payment_terms_type == 'in_house' ? date('M d, Y', strtotime($data->downpayment_due_date)) : null}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Total Balance for In-house Financing</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'in_house' ? $data->total_balance_in_house : null, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Interest Rate at 7% Per Annum</td>
                        <td>Php</td>
                        <td colspan="3">{{$data->payment_terms_type == 'in_house' ? $data->factor_rate : null}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Number of Years</td>
                        <td></td>
                        <td colspan="3">{{$data->payment_terms_type == 'in_house' ? $data->number_of_years : null}}</td>
                    </tr>
                    <tr>
                        <td class="border-bottom" style="border: none;"></td>
                        <td class="blue-bg border-bottom" colspan="2" style="position: relative; overflow: hidden;">Monthly Amortization</td>
                        <td class="border-bottom">Php</td>
                        <td class="border-bottom">{{number_format($data->payment_terms_type == 'in_house' ? $data->monthly_amortization : null, 2)}}</td>
                        <td class="blue-bg border-bottom">Due Date</td>
                        <td class="border-bottom">{{$data->payment_terms_type == 'in_house' ? date('M d, Y', strtotime($data->monthly_amortization_due_date)) : null}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td style="border: none;" colspan="6">
                            <ul class="list">
                                <li style="list-style: none; margin-left: -30px;"><span>SPLIT PAYMENT</span></li>
                                <li style="{{ $data->split_cash ? 'list-style: disc !important;' : null}}"><span>SPLIT CASH</span></li>
                                <li style="{{ $data->split_downpayment ? 'list-style: disc !important;' : null}}"><span>SPLIT DP</span></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Number of Splits</td>
                        <td colspan="4">{{$data->payment_terms_type == 'cash' ? $data->number_of_cash_splits : $data->number_of_downpayment_splits}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg" colspan="2">Split Payment Amount</td>
                        <td>Php</td>
                        <td colspan="3">{{number_format($data->payment_terms_type == 'cash' ? $data->split_payment_amount : $data->split_downpayment_amount, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="border: none;"></td>
                        <td class="blue-bg">Start Date</td>
                        <td colspan="2">
                            @if ($data->split_cash)
                                {{date('M d, Y', strtotime($data->split_cash_start_date))}}
                            @endif
                            @if ($data->split_downpayment)
                                {{date('M d, Y', strtotime($data->split_downpayment_start_date))}}
                            @endif
                        </td>
                        <td class="blue-bg">End Date</td>
                        <td colspan="2">
                            @if ($data->split_cash)
                                {{date('M d, Y', strtotime($data->split_cash_end_date))}}
                            @endif
                            @if ($data->split_downpayment)
                                {{date('M d, Y', strtotime($data->split_downpayment_end_date))}}
                            @endif
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td class="blue-bg" style="padding: 5px; width: 50px; text-align: center;">3</td>
            <td class="blue-bg b-none" style="text-align: left; padding-left: 5px;">
                Terms and Conditions
            </td>
        </tr>

        <tr>
            <td>                
            </td>
            <td style="padding: 0;">
            <strong style="font-size: 8px;">RESERVATION AGREEMENT NO. 1</strong>
            <ol class="terms" style="font-size: 8px; padding: 0; margin: 0;">
                <li>The reservation for the Property specified above is good only for a period of thirty (30) calendar days from the Buyer’s/Buye rs’ payment of the Reservation Fee. The Buyer(s) understand(s) that the same is non-refundable and non-transferable. In the event that the Buyer(s) cancel(s) his/her/its/their reservation or fail to pay the down payment or the full payment on or before the above-mentioned schedule, the Buyer(s) understand(s) that his/her/its/their reservation shall lapse and the Reservation Fee shall be forfeited in favor of the Seller.</li>
                <li>All payments shall be made payable to the Seller, EARTH &amp; SHORE LEISURE COMMUNITIES CORPORATION (ESLCC).</li>
                <li>The Buyer(s) understand(s) and agree(s) that the monthly amortization of the balance of the Selling Price shall commence thirty (30) days after he /she/it/they pay the down payment regardless whether the Contract-to-Sell has been delivered or not.</li>
                <li>The Buyer(s) choosing the in-house assisted financing hereby declares his/her agreement with the computation indicated above and undertakes to produce any additional document that may be required by ESLCC.</li>
                <li>The Buyer(s) hereby certify(ies) that he/she/it/they has/have personally examined the Property subject in this Agreement and have found the same to be satisfactory.</li>
                <li>The Buyer(s) agree(s) that for any reason, either legal or through inadvertence, the reserved Property for him/her/it/them becomes unavailable, ESLCC at its option may either replace the said Property with another property of equal value or this Agreement may be rescinded subject to the refund of all payments he/she/it/they made."</li>
                <li>The Buyer(s) warrant(s) that all information provided herein are true and correct as of the date hereof. The Buyer(s) understand(s) that the Seller has to be informed in writing of any changes thereon. Further, the Seller shall have the right to solely rely on the information the Buyer(s) provided and shall not be held responsible for any error, non-communication or miscommunication regarding the same. The Buyer(s) also warrant that the funds used and to be used in the purchase of the Property has been and will be obtained through legitimate means and do not and will not constitute all or part of the proceeds of any unlawful activity under applicable laws.</li>
                <li>The Buyer(s) hereby hold(s) the Seller free and harmless from any incident, claim, action, or liability arising from the breach of any of the warranties stated herein. Furthermore, the Seller is authorized to prove to any government body or agency any information pertaining to this transaction if so warranted and required under existing laws.</li>
                <li>The Buyer(s) understand(s) that any representations or warranties by the Property Consultant, Sales Manager and Sales Director who handled this sale which are not stipulated in this Agreement shall not be binding to ESLCC unless affirmed in writing by an authorized officer of ESLCC.</li>
                <li>The Buyer(s) hereby fully consent(s) the Seller to collect, record, organize, store, update, use, consolidate, block, erase o r otherwise process his/her/its/their information and of this transaction, whether personal, sensitive or privileged. In this connection, the Buyer(s) acknowledge(s) that he/she/it/they has/have read, understood and/or has/have been duly informed of t he terms and conditions pertaining to the Seller's data privacy practices and the Buyer(s) fully conform(s) thereto. Such terms and conditions are reflected in the Data Privacy Policy at: https://camayacoast.com/CamayaPaymentPortal%20-%20Privacy%20Policy.pdf</li>
                <li>This Reservation Agreement is executed in the Philippines. The same shall also be governed by, and construed under the laws of the Philippines, regardless of the laws that might otherwise govern under applicable principles of conflicts of law. Any and all disputes arising out of this Reservation Agreement shall be subject to the exclusive jurisdiction of the proper court of Balanga, Bataan. The Buyer(s) waive(s) any other venue and the defense of an inconvenient forum.</li>
            </ol>
            <p style="font-size: 7.5px;"><strong>***This Agreement (Reservation Agreement No. 1) may be revoked by a subsequent Agreement (Reservation Agreement No. 2) should the Buyer(s) change the terms of payment of the foregoing sale.</strong></p>
            </td>
        </tr>

        <tr>
            <td style="padding: 5px; text-align: center;">&nbsp;</td>
            <td class="b-none">
                <p><strong>Client Signature:</strong></p>

                <div style="padding: 5px; border-bottom: solid 1px #111; width: 200px;">
                    {{$data->client->first_name}} {{$data->client->last_name}}
                    @if ($data->client->spouse && $data->client->spouse->spouse_first_name)
                        <span>&nbsp;/&nbsp;{{$data->client->spouse->spouse_first_name}} {{$data->client->spouse->spouse_last_name}}</span>
                    @endif
                    @if ($data->co_buyers)
                        @foreach ($data->co_buyers as $co_buyer) 
                            / {{$co_buyer['details']['first_name']}} {{$co_buyer['details']['last_name']}}
                        @endforeach
                    @endif
                </div>
                Date
            </td>
        </tr>
    </table>
</div>
        
<!-- <div class="page-break"></div>  -->



        