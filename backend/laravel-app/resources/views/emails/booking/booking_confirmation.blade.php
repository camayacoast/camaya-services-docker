<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>

<body>
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
        }

        .footer {
            width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>

    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="header">
                            {{-- <a href="{{ env('CAMAYA_PAYMENT_PORTAL') }}" style="display: inline-block;"> --}}
                            <img src="{{ env('APP_URL').'/images/camaya-logo.png' }}" style="vertical-align: middle;" width='100' />
                            {{-- </a> --}}
                        </td>
                    </tr>


                    <!-- Email Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                            <!-- Body content -->
                                <tr>
                                    <td class="content-cell">
                                        <h4 style="text-align: center;">BOOKING CONFIRMATION</h4>

                                        <p>Dear {{$booking['customer']['first_name']}} {{$booking['customer']['last_name']}},</p>

                                        <p>Your booking with Camaya Coast has been confirmed!</p>

                                        <p>Attached to this email is your booking details, availed products and promos, and other relevant information.</p>

                                        <p>Booking reference no.: <strong>{{$booking['reference_number']}}</strong></p>

                                        <!-- <p><strong>Pre-arrival requirements</strong></p>
                                        <ol>
                                            <li>Booking Confirmation and QR code.</li>
                                            <li>Government Issued ID or any equivalent ID.</li>
                                            {{-- <li>QR Coded Tourist Pass from Bataan VIS.I.T.A (Visitor Information and Travel Assistant) website. Register at <a href="https://visita.beholdbataan.ph" target="_blank">https://visita.beholdbataan.ph</a></li> --}}
                                        </ol> -->

                                        {{-- <p>For any concerns or clarifications, please contact our reservations team at 0917 711 2638 / 0917 543 0560 or email us at <a href="mailto:reservations@camayacoast.com">reservations@camayacoast.com</a>.</p> --}}

                                        <p>
                                            For any concerns or clarifications, please contact our reservations team.<br/><br/>
        
                                            <strong>Commercial Guests: </strong>
                                            <!-- 0917 711 2638<br/>
                                            0917 543 0560<br/><br/> -->
                                            reservations@camayacoast.com<br/>

                                            <strong>Property Owner: </strong>
                                            <!-- 0917-520-6192<br/> -->
                                            hoabooking@camayacoast.com<br/><br/>

                                            <!-- Or email us at <a href="mailto:reservations@camayacoast.com">reservations@camayacoast.com</a><br/><br/> -->
                                        </p>

                                        <p>Regards,<br/>Camaya Coast team</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="content-cell" align="center">
                                        &copy; Camaya Coast. All rights reserved.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
