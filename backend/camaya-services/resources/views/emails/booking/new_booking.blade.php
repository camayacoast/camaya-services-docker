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
                                        <h4 style="text-align: center;">NEW BOOKING</h4>

                                        <p>Dear {{$booking['customer']['first_name']}},</p>

                                        <p>Thank you for booking your visit to Camaya Coast.</p>

                                        <p>Booking reference no.: <strong>{{$booking['reference_number']}}</strong></p>

                                        <p>Attached are the details of your booking request.</p>

                                        <p>This email only acknowledges your booking request and has been marked as PENDING.</p>

                                        <p>A separate email will be sent once your booking has been paid and/or confirmed.</p>

                                        <p>Below are the options to settle your balance. Pay via credit card using PayPal or via bank deposit / online bank transfer:</p>

                                        <p>Maya / GCash</p>

                                        <table border="0" cellspacing="0" cellpadding="0" style="float: left; margin-right: 8px;">
                                            <tr>
                                                <td>
                                                    <table border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td align="center" style="border-radius: 3px;" bgcolor="#3b7bbf"><a href="{{env('APP_URL')}}/api/booking/public/payment/paymaya/request/{{$booking['reference_number']}}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; text-decoration: none;border-radius: 3px; padding: 12px 18px; border: 1px solid #3b7bbf; display: inline-block;">Pay using Maya or GCash</a></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
    
                                        <div style="clear:both;"></div>

                                        <p style="margin-top: 16px;">PayPal</p>

                                        <table border="0" cellspacing="0" cellpadding="0" style="float: left; margin-right: 8px;">
                                            <tr>
                                                <td>
                                                    <table border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td align="center" style="border-radius: 3px;" bgcolor="#3b7bbf"><a href="{{env('APP_URL')}}/api/booking/public/payment/paypal/request/{{$booking['reference_number']}}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; text-decoration: none;border-radius: 3px; padding: 12px 18px; border: 1px solid #3b7bbf; display: inline-block;">Pay using Credit Card via Paypal</a></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <div style="clear:both;"></div>

                                        {{-- Hide bank details request as of April 5, 2023 --}}
                                        {{-- <p style="margin-top: 16px;">
                                            Bank: <strong>Metrobank </strong><br/>
                                            Account number: <strong>442-7-442-01632-8 </strong><br/>
                                            Account name: <strong>Earth and Shore Tourism Landholdings Corp. </strong><br/>
                                            Bank branch: <strong>Rockwell Makati </strong>
                                        </p>

                                        <p>Kindly settle the amount within 24 hours to confirm your booking and send us a copy of the deposit slip at <a href="mailto:bankpayments@camayacoast.com">bankpayments@camayacoast.com</a> with the following details:</p>

                                        <p>
                                            <strong>Customer name <span style="font-size: 12px;">(Registered name on the booking)</span>:</strong> <br/>
                                            <strong>Date of visit <span style="font-size: 12px;">(for hotel guest/s please indicate date of arrival and departure)</span>:</strong> <br/>
                                            <strong>Booking reference no.:</strong><br/>
                                            <strong>Date of deposit:</strong><br/>
                                            <strong>Amount deposited:</strong>
                                        </p>

                                        <p>Please allow 24 &ndash; 48 hours for Bank deposit verification prior receipt of confirmation of visit.</p>

                                        <p>To change your mode of payment and settle your invoice via Online Payment or Credit Card, please click the link below:</p> --}}

                                        <p style="margin-top: 16px;">
                                            For any concerns or clarifications, please contact our reservations team.<br/><br/>
        
                                            <strong>Commercial Booking Reservations:</strong><br/>
                                            {{-- 0917 711 2638<br/> --}}
                                            {{-- 0917 543 0560<br/><br/> --}}
                                            reservations@camayacoast.com<br/><br/>

                                            <strong>Property Owner/HOA Booking Reservations:</strong><br/>
                                            {{-- 0917-520-6192<br/> --}}
                                            hoabooking@camayacoast.com<br/><br/>

                                            Or email us at <a href="mailto:reservations@camayacoast.com">reservations@camayacoast.com</a><br/><br/>
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
