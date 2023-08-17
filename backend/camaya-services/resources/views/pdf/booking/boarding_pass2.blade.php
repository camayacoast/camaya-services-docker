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

        /* @page {
            size: 21cm 29.7cm;
            margin: 30mm 45mm 30mm 45mm;
            /* change the margins as you want them to be. */
        /* } */

        .trips td, .trips th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .trips th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #4CAF50;
            color: white;
        }

        .trips tr:nth-child(even){background-color: #f2f2f2;}

    </style>
    
    <div class="wrapper">
        @foreach ($guests as $guest)
            <div><img src="images/camaya-logo.png" width="100" style="display: inline-block; float: left; vertical-align: middle;" /><div style="width: 100%; float: right; margin-left: 140px; vertical-align: middle; text-align: right;">
                    @if (count($guest['tripBookings']))
                        <strong>E-BOARDING PASS</strong>
                        @else
                        <strong>E-GATE PASS</strong>
                    @endif
            </div></div>

            <table style="width: 100%; clear:both;" border="0">
                <tr>
                    <td align="center"><h1 style="font-size: 50px;">{{strtoupper($guest['first_name'])}} {{strtoupper($guest['last_name'])}}</h1></td>
                </tr>
                <tr>
                    <td align="center">
                        <img src='data:image/png;base64,{{DNS1D::getBarcodePNG($guest["reference_number"], "C128",4, 120)}}' style="margin-bottom: 25px;"/><br/>
                        <img style="display: block; width: 40%;" src="data:image/png;base64, {!! base64_encode(QrCode::format('svg')->size(500)->errorCorrection('H')->generate($guest['reference_number'])) !!} "/>
                    </td>
                </tr>
                <tr>
                    <td align="center"><h1 style="font-size: 40px;">{{$guest['reference_number']}}</h1></td>
                </tr>
                
                {{-- @if (count($guest['tripBookings']))
                    <div></div>
                @else
                    <div class="page-break"></div>
                @endif --}}

            </table>
            
            @if (count($guest['tripBookings']))
                <div style="border-radius: 12px; overflow: hidden; border: solid 1px gainsboro;">
                    <table class="trips" style="width: 100%; border-radius: 8px; border: none;" border="1" cellspacing="0">
                        <tr>
                            <th style="text-align: center;">Schedule</th>
                            <th style="text-align: center;">Seat #</th>
                            <th style="text-align: center;">Origin</th>
                            <th style="text-align: center;">Destination</th>
                            <th style="text-align: center;">Transportation</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                        @foreach (
                            collect($guest['tripBookings'])->sortBy(function ($item) {
                                    return strtotime($item['schedule']['trip_date'].' '.$item['schedule']['start_time']);
                            })
                            
                            as $trip_booking)
                                @if ($trip_booking['status'] != 'cancelled')
                                    <tr>
                                        <td>
                                            <small>
                                                <div><strong>{{date('d M Y', strtotime($trip_booking['schedule']['trip_date']))}}</strong></div>
                                                <div>ETD: {{date('h:i A', strtotime($trip_booking['schedule']['start_time']))}}</div>
                                                <div>ETA: {{date('h:i A', strtotime($trip_booking['schedule']['end_time']))}}</div>
                                            </small>
                                        </td>

                                        @if ( in_array ( 'VIP', collect($guest['guestTags'])->pluck('name')->all() )) 
                                            <td style="text-align: center;">{{$trip_booking['seat_number']}}</td>
                                        @else
                                            <td style="text-align: center;">Free seating</td>
                                        @endif

                                        <td style="text-align: center;">{{$trip_booking['schedule']['route']['origin']['code']}}</td>
                                        <td style="text-align: center;">{{$trip_booking['schedule']['route']['destination']['code']}}</td>
                                        {{-- <td style="text-align: center;">{{$trip_booking['schedule']['transportation']['name']}}</td> Hide vessel name 03/10/2023  --}}
                                        <td style="text-align: center;">Camaya Ferry</td>
                                        <td style="text-align: center; text-transform: capitalize;">{{$trip_booking['status']}}</td>
                                    </tr>
                                @endif
                        @endforeach
                        <!-- <tr>
                            <th>Schedule</th>
                            <th>Seat #</th>
                            <th>Vehicle</th>
                            <th>Origin</th>
                            <th>Destination</th>
                        </tr>
                        <tr>
                            <td>
                                <div>Departure: 07:00:00</div>
                                <div>Arrival: 09:00:00</div>
                                <div>2020-12-01</div>
                            </td>
                            <td align="center">1A</td>
                            <td align="center">MV XGC Voyager</td>
                            <td align="center">EST</td>
                            <td align="center">CMY</td>
                        </tr>

                        <tr>
                            <td>
                                <div>Departure: 17:00:00</div>
                                <div>Arrival: 19:00:00</div>
                                <div>2020-12-01</div>
                            </td>
                            <td align="center">1A</td>
                            <td align="center">MV XGC Voyager</td>
                            <td align="center">CMY</td>
                            <td align="center">EST</td>
                        </tr> -->
                    </table>
                </div>
                {{-- <p style="color: red;">All passengers must be checked-in 30 mins. before ferry departure as cut-off time. Passenger who arrived beyond the cut-off time will not be able to board the ferry.</p> --}}

                <small style="color: red;">
                    <ul>
                        <li>Check-in counters open as early as 1.5 hours before departure, and closes 30 minutes before departure.</li>
                        <li>Boarding gate opens 1 hour before departure, and closes 15 minutes before departure.</li>
                        <li>Guests will not be allowed to board the ferry after check-in counters and boarding gates are closed.</li>
                    </ul>
                </small>

            @endif
            
            <div class="page-break"></div>

        @endforeach
    </div>
    </body>
</html>

