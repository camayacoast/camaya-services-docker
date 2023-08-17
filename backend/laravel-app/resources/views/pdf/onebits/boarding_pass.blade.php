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
            } */

            .wrapper{
                margin-top: 50px;
                margin-bottom: 50px;
            }

            .trips td, .trips th {
                border: 1px solid #ddd;
                padding: 8px;
            }

            .trips th {
                padding-top: 12px;
                padding-bottom: 12px;
                text-align: left;
                background-color: navy;
                color: white;
            }

            .trips tr:nth-child(even){background-color: #f2f2f2;}

        </style>
        @foreach($tickets as $ticket)
        <div class="wrapper">
            <div>
                <img src="images/1bits-logo.png" width="100" style="display: inline-block; float: left; vertical-align: middle;" />
                <div style="width: 100%; float: right; margin-left: 140px; vertical-align: middle; text-align: right;">
                <h1 style="font-size: 2.5rem; overflow: hidden;">{{strtoupper($ticket['passenger']['first_name'])}} {{strtoupper($ticket['passenger']['last_name'])}}<br/>
                    <small style="font-size: 14px">E-BOARDING PASS</small></h1>
            </div></div>

            <table style="width: 100%; clear:both;" border="0">
                <tr>
                    <td align="center">
                        <img src='data:image/png;base64,{{DNS1D::getBarcodePNG($ticket["reference_number"], "C128",4, 120)}}' style="margin-bottom: 25px;"/><br/>
                        <img style="display: block; width: 40%;" src="data:image/png;base64, {!! base64_encode(QrCode::format('svg')->size(500)->errorCorrection('H')->generate($ticket['reference_number'])) !!} "/>
                    </td>
                </tr>
                <tr>
                    <td align="center"><h1 style="font-size: 40px;">{{$ticket['reference_number']}}</h1></td>
                </tr>
            </table>

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
                        
                        @if ($ticket['status'] != 'cancelled')
                            <tr>
                                <td>
                                    <small>
                                        <div><strong>{{date('d M Y', strtotime($ticket['schedule']['trip_date']))}}</strong></div>
                                        <div>ETD: {{date('h:i A', strtotime($ticket['schedule']['start_time']))}}</div>
                                        <div>ETA: {{date('h:i A', strtotime($ticket['schedule']['end_time']))}}</div>
                                    </small>
                                </td>

                                <td style="text-align: center;">{{$ticket['trip']['seat_number']}}</td>
                                <td style="text-align: center;">{{$ticket['schedule']['route']['origin']['code']}}</td>
                                <td style="text-align: center;">{{$ticket['schedule']['route']['destination']['code']}}</td>
                                <td style="text-align: center;">{{$ticket['schedule']['transportation']['name']}}</td>
                                <!-- {{-- <td style="text-align: center;">{{$ticket['schedule']['transportation']['name']}}</td> Hide vessel name 03/10/2023  --}} -->
                                <!-- <td style="text-align: center;">Camaya Ferry</td> -->
                                <td style="text-align: center; text-transform: capitalize;">{{$ticket['trip']['status']}}</td>
                            </tr>
                        @endif
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
        </div>
        
        @endforeach
    </body>
</html>

