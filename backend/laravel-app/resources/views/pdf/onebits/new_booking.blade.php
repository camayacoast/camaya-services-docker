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

        body {
            font-size: 11px;
        }

        .emphasize{
            color: black;
        }

        h3{
            text-decoration: underline;
        }

        /* @page {
            size: 21cm 29.7cm;
            margin: 30mm 45mm 30mm 45mm;
            /* change the margins as you want them to be. */
        /* } */
        table td, table th {
                border: 1px solid #ddd;
                padding: 8px;
        }

        table th {
            padding-top: 4px;
            padding-bottom: 4px;
            text-align: left;
            /* background-color: navy;
            color: white; */
        }

    </style>
    
    <div class="wrapper">
        <div style="text-align: center;"><img src="images/1bits-logo.png" width="100" /></div>
        <h2 style="text-transform: uppercase; text-align: center;">
            CONFIRMED BOOKING
        </h2>

        <div>
            <p>Booking code: {{$reference_number}}</p>
            <p>Booking date: {{$booking[0]['booking_date']}}</p>
        </div>

        <div>
            <h3>Booking Details</h3>
            <h4>Booked by: </h4>
            <p>Contact Number: {{$booking[0]['contact_number']}}</p>
            <p>Email Address: {{$booking[0]['email']}}</p>
        </div>

        <div>
            <!-- <h3>TRIP DETAILS</h3>
            <table>
                <tr>
                    <th>Trip</th>
                    <th>No. of guests</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Total: {{$booking[0]['total_booking_amount']}}</td>
                </tr>
            </table> -->

            <h4>Passenger List</h4>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Type</th>
                    <th>Rate</th>
                    <th>Discount</th>
                </tr>

                @php
                    $trips = array();
                    $trips[0] = $tickets[0]['trip']['trip_number'];
                    if(isset($tickets[1]['trip'])){
                        $trips[1] = $tickets[0]['trip']['trip_number'] === $tickets[1]['trip']['trip_number'] ? null : $tickets[1]['trip']['trip_number'];
                    }
                    
                @endphp

                @foreach ($booking[0]['trips'] as $trip)

                    <tr>
                        <th colspan="5" style="text-align: left;">{{$trip['origin_code']}} - {{$trip['destination_code']}}</th>
                    </tr>
                    @foreach ($tickets as $passenger)
                        @if($passenger['trip']['trip_number'] === $trip['trip_number'])
                            <tr>
                                <td>{{$passenger['passenger']['first_name']}} {{$passenger['passenger']['last_name']}}</td>
                                <td>{{$passenger['passenger']['age']}}</td>
                                <td>{{$passenger['passenger']['type']}}</td>
                                <td>{{$passenger['amount']}}</td>
                                <td>
                                    @if($passenger['discount'])
                                        {{$passenger['discount']}}
                                    @else
                                        0.00
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endforeach

                
                <tr>
                    <th colspan="5" style="text-align: right;">Total amount: {{$booking[0]['total_booking_amount']}}</th>
                </tr>
            </table>
        </div>

        <div>
            <h3>TRIP SCHEDULE</h3>
            <table>
                <tr>
                    <th>Transportation</th>
                    <th>Route</th>
                    <th>Trip date</th>
                    <th>ETD</th>
                    <th>ETA</th>
                </tr>

              
                <tr>
                    <td>{{$tickets[0]['trip']['schedule']['transportation']['name']}}</td>
                    <td>{{$booking[0]['trips'][0]['origin_code']}} - {{$booking[0]['trips'][0]['destination_code']}}</td>
                    <td>{{date('F d, Y', strtotime($booking[0]['trips'][0]['Schedule']['trip_date']))}}</td>
                    <td>{{$booking[0]['trips'][0]['start_time']}}</td>
                    <td>{{$booking[0]['trips'][0]['end_time']}}</td>
                </tr>

                @if(isset($booking[0]['trips'][1]))

                <tr>
                    <td>{{$tickets[1]['trip']['schedule']['transportation']['name']}}</td>
                    <td>{{$booking[0]['trips'][1]['origin_code']}} - {{$booking[0]['trips'][1]['destination_code']}}</td>
                    <td>{{date('F d, Y', strtotime($booking[0]['trips'][1]['Schedule']['trip_date']))}}</td>
                    <td>{{$booking[0]['trips'][1]['start_time']}}</td>
                    <td>{{$booking[0]['trips'][1]['end_time']}}</td>
                </tr>

                @endif
          
            </table>
        </div>

        <div>
            <h3 style="text-transform: uppercase">PAYMENT STATUS: CONFIRMED AND {{$booking[0]['payment_status']}}</h3>
            <table>
                <tr>
                    <th>Payment Provider</th>
                    <th>Total amount</th>
                    <th>Payment Date</th>
                </tr>

                <tr>
                    <td>{{$booking[0]['payment_provider']}}</td>
                    <td>{{$booking[0]['total_booking_amount']}}</td>
                    <td>{{$booking[0]['paid_at']}}</td>
                </tr>
            </table>
        </div>

        <br/>
        <br/>
        <div>
            <h3>TRIP REMINDERS</h3>
            <p>Please proceed to <strong>1BITS Ticketing booth Counter 2A</strong> upon your arrival in the Terminal. Kindly present this Reservation Confirmation with your <strong>valid IDs and QR codes</strong> to get your Boarding Pass. We are located at <strong>Esplanade Seaside Terminal D.W. Diokno Seaside Blvd. Pasay City (beside 7/11 Convenience Store and Vikings Buffet Restaurant)</strong></p>
            <ul>
                <li>You may check-in <strong>1 hour</strong> before departure and closes 15 minutes before departure.</li>
                <li>Guests will not be allowed to board the ferry after check-in counters and boarding gates are closed.</li>
                <li>For safety and security purposes, a normal routine baggage check shall be done at 1Bataan ITS terminals.</li>
                <li>Only 1 hand carry luggage with a maximum weight of <strong>7 kg with a size of 56cm (22in) x 36cm (14in) x 23cm (10in) is allowed per passenger.</strong></li>
                <li>Change of name of a passenger on the same day of the scheduled travel is not allowed and will not be entertained. For modifications, please send an email at <a href="mailto:booking@1bataanits.ph">booking@1bataanits.ph</a> at least 3-5 days prior to the travel date. <strong>Our team will be available for further assistance</strong>.</li>
            </ul>
            <p><strong>For safety reasons, the following shall not be allowed to board the ferry:</strong></p>
            <ul>
                <li>Pregnant women</li>
                <li>Pets</li>
            </ul>

            <p><strong>Other prohibited items inside the ferry:</strong></p>
            <ul>
                <li>Overweight luggage, or packages exceeding allowed specifications.</li>
                <li>Any flammable, hazardous or illegal substances</li>
                <li>Food cannot be consumed inside of the ferry while onboard.</li>
            </ul>
        </div>

        <div>
            <h3>CANCELLATION POLICY</h3>
            <ul>
                <li>In an unfortunate case that a trip is cancelled due to the unavailability of the vessel, the ticket shall be refunded to the passenger in full.</li>
                <li>In the event of cancellations due to non-conducive/unfavorable weather conditions, tickets shall be applied on the next available trip after the ban on sailing is lifted by the Philippine Coast Guard authorities.</li>
                <li>In cases of cancellations due to unexpected/unforeseen events and/or situations beyond our control, trips may be cancelled without prior notice.</li>
            </ul>
        </div>

        <!-- <div>
            <h3>RETURN/REFUND POLICY</h3>
            <ul>
                <li>Customers may send an email Refund request to <strong>booking@1bataanits.ph</strong> using the registered email address of the transaction. </li>
                <li>No show on the date of visit will be automatically <strong>subject for forfeiture and non-refundable.</strong></li>
                <li>Refund is applicable to following reasons below:
                    <br/>
                    <ol>
                        <li>Acts of God or unforeseeable circumstances.</li>
                        <li>Ferry Technical Reason / Vessel unavailability</li>
                    </ol>
                </li>
                <li class="emphasize">For <strong>Credit Card/Debit Card</strong>, reversal is 7-15 working days to cardholder's account. Refund request must be submitted within 100 days from purchase date for it to be facilitated via email otherwise, refund will be directed over our Reservations team.</li>
                <li class="emphasize">For <strong>Gcash or Paymaya</strong> payment channels, if refund request is placed within 100 days from purchase date, posting to account is real time. Beyond this, refund should be facilitated over our Reservations team. Once refund has been processed, proof of payment will be sent via email to notify customers.</li>
            </ul>
        </div> -->
        
    </div>
    </body>
</html>

