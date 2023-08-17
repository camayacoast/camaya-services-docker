<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    </head>
    <body>
    <style>
        .page-break {
            page-break-after: always;
        }

        .page-break-before {
            page-break-before: always;
        }

        /* @page {
            size: 21cm 29.7cm;
            margin: 30mm 45mm 30mm 45mm;
            /* change the margins as you want them to be. */
        /* } */

    </style>
    
    <div class="wrapper">

        <div>
            <img src="images/camaya-logo.png" width="140" />
            <h3 style="float: right;">VOUCHER CONFIRMATION</h3>

            {{-- <div>
                @if (isset($customer))
                    Name: {{$customer['first_name']}} {{$customer['last_name']}}<br>
                    Contact Number: {{$customer['contact_number']}}<br>
                    Email Address: {{$customer['email']}}
                @else 
                    -
                @endif
            </div> --}}
        </div>

        {{-- <div style="text-align: center;"><img src="images/camaya-logo.png" width="140" /></div>
        <h3 style="text-transform: uppercase; text-align: center;">VOUCHER CONFIRMATION</h3> 


        <table style="width: 100%; margin-top: 48px; margin-bottom: 48px;" border="1" cellpadding="0" cellspacing="0">
            <tr>
                <th>Voucher name</th>
                <th>Voucher code</th>
                <th>Transaction ref #</th>
                <th>Voucher Status</th>
                <th>Payment Status</th>
                <th>Price</th>
            </tr>
            @foreach ($paid_vouchers as $generated_voucher)
                <tr>
                    <td>{{$generated_voucher['voucher']['name']}}</td>
                    <td><strong>{{$generated_voucher['voucher_code']}}</strong></td>
                    <td>{{$generated_voucher['transaction_reference_number']}}</td>
                    <td>{{$generated_voucher['voucher_status']}}</td>
                    <td>{{$generated_voucher['payment_status']}}</td>
                    <td>P {{number_format($generated_voucher['price'], 2)}}</td>
                </tr>
            @endforeach
        </table>  --}}

        @foreach ($paid_vouchers as $generated_voucher)
            <div style="background: url({{$generated_voucher['availability'] == 'for_dtt' ? 'images/save-now-travel-later-dtt.jpg' : 'images/save-now-travel-later-ovn.jpg' }}); background-size: cover; background-repeat: no-repeat; border-radius: 12px; width: 100%; display: block; border: solid 1px #fff; margin-bottom: 16px;">
                
                {{-- <div style="float: right; margin-right: 8px; margin-top: 8px;"><img src="images/camaya-logo.png" width="90" /></div> --}}
                
                <div style="text-align: right; clear:both; padding-right: 16px; padding-bottom: 16px; width: 250px; margin-left: auto; margin-top: 12px;">
                    <div><img src="images/camaya-logo.png" width="90" /></div>
                    
                    <p>
                        {{$generated_voucher['voucher']['name']}}
                        <br><small style="text-transform: uppercase; font-weight: bold;">{{str_replace("_", " ", $generated_voucher['mode_of_transportation'])}}</small>
                    </p>

                    <h3 style="color: rgb(31, 131, 169)">{{$generated_voucher['voucher_code']}}
                        <br/><small style="font-size: 12px; font-weight: normal; color: #aaa;">VOUCHER CODE</small>
                    </h3>

                    <small>
                        Status: <span style="text-transform: uppercase; font-weight: bold; color: limegreen;">{{$generated_voucher['payment_status']}}</span>
                        <br>Issued to:
                            @if (isset($generated_voucher['customer']))
                            {{$generated_voucher['customer']['first_name']}} {{$generated_voucher['customer']['last_name']}}
                            @else
                                -
                            @endif
                        <br><br>
                        {{-- Validity: {{date('M d, Y', strtotime($generated_voucher['validity_start_date']))}} - {{date('M d, Y', strtotime($generated_voucher['validity_end_date']))}} --}}
                    </small>
                </div>
            </div>
        @endforeach

        <div class="page-break-before"></div>

        <div>
            <h3>VOUCHER PURCHASE DETAILS</h3>

            <table style="width: 100%; margin-top: 20px; margin-bottom: 20px;" border="1" bordercolor="#eaeaea" cellpadding="8" cellspacing="0">
                <tr>
                    <th>Voucher</th>
                    <th>Transaction Reference No.</th>
                    <th>Voucher Status</th>
                    <th>Payment Status</th>
                    <th>Price</th>
                </tr>
                @foreach ($paid_vouchers as $generated_voucher)
                    <tr style="vertical-align:top">
                        <td>
                            <small><strong>{{$generated_voucher['voucher']['name']}}</strong></small><br><br>
                            <span style="white-space: pre-wrap;"><small style="font-size: 12px;"><strong>Inclusions:</strong><br>{{$generated_voucher['voucher']['description']}}</small></span>
                        </td>
                        <td align="center"><small>{{$generated_voucher['transaction_reference_number']}}</small></td>
                        <td align="center"><small style="text-transform: uppercase;">{{$generated_voucher['voucher_status']}}</small></td>
                        <td align="center"><small style="text-transform: uppercase;">{{$generated_voucher['payment_status']}}</small></td>
                        <td><small>P {{number_format($generated_voucher['price'], 2)}}</small></td>
                    </tr>
                @endforeach
            </table>
        
            <p style="text-align: right; font-weight: 700; margin-top: 0;">Total Amount: P {{number_format(collect($paid_vouchers)->sum('price'), 2)}}</p>
        
        </div>

        <div class="page-break-before"></div>

        <div>
            <h3 style="text-align: center;">TERMS AND CONDITIONS</h3>

            <div>
                <ul>
                    <li>Voucher is not applicable in conjunction with other hotel and resort promos.</li>
                    <li>Voucher is not convertible to cash.</li>
                    <li>Voucher is transferable to other guests.</li>
                    <li>Prior reservations is required and must be made at least 72 hours before the trip date. Subject to availability.</li>
                    <li>Strictly by booking. Camaya Coast reserves the right to decline reservation based on schedule and slot availability.</li>
                    <li>For <strong>Day Tour</strong> vouchers, hotel accommodation is not included.</li>
                    <li>For Day Tour vouchers with Dining Credit, claiming of credits will be at DTT Booth near Army Navy on the day of trip date.</li>
                    <li>For <strong>Overnight</strong> vouchers, incidental charges will be on a personal account of the guest.</li>
                    <li>All bookings are guaranteed and no-show on the day of trip date shall forfeit the voucher.</li>
                    <li>Extra Person chargeable of Php 1,000/head/night.</li>
                    <li>All ages are allowed and subject to the government restrictions and guidelines. <strong>In the event that any guest’s travel/entry should be delayed or disallowed by any government authority, including those designated at government checkpoint/s, Camaya Coast Resort Management shall not be liable for said delay or prohibition to travel.</strong></li>
                </ul>
            </div>

            <div>
                <strong>Promo Voucher Booking &amp; Reservations Guidelines:</strong>
                <ul style="margin-top: 0px; margin-bottom: 0px;">
                    <li>Please email us to secure a time slot at least 72 hours before your preferred date.</li>
                    <li> Hotel Room and Ferry Schedule are subject to availability.</li>
                </ul>
            </div>

            <div>
                <p><strong>Reservations Email:</strong> <a href="mailto:reservations@camayacoast.com">reservations@camayacoast.com</a></p><br/>
                {{-- <p style="margin-bottom: 0px;"><strong>Contact Numbers:</strong></p>
                <div class="row">
                    <div class="col-md-4">
                        <ul style="margin-top: 0px;">
                            <li><span style="margin-left: 2px;">- 0917 520 6192 | 0917 543 0560</span></li>
                        </ul>
                    </div>
                </div> --}}
                <p>
                    <strong>Commercial Reservation:</strong><br/>
                    <!-- 0917 711 2638<br/>
                    0917 543 0560<br/><br/> -->
                    reservations@camayacoast.com<br/>

                    <strong>Property Owner/HOA Booking Reservations:</strong><br/>
                    {{-- 0917-520-6192<br/> --}}
                    hoabooking@camayacoast.com<br/><br/>
                </p>
        </div>
        
        <p style="text-align: center; margin-top: 20px;"><strong>Thank you and we look forward to seeing you at Camaya Coast!</strong></p>

    </div>
    </body>
</html>

