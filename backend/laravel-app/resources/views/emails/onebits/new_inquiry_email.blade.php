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

        body {
            color: #1434A4 !important;
        }

        .wrapper {
            background-color: rgb(167, 199, 231, 1) !important;
        }
        .body {
            background-color: rgb(167, 199, 231, 1) !important;
            border-bottom: 1px solid rgb(167, 199, 231, 1) !important;
            border-top: 1px solid rgb(167, 199, 231, 1) !important;
        }
    </style>

    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">

                    <tr>
                        <td class="header">
                            <img src="{{ env('APP_URL').'/images/1bits-logo.png' }}" style="vertical-align: middle;" width='100' />
                        </td>
                    </tr>

                    <!-- Email Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                
                                <!-- Body content -->
                                <tr>
                                    <td class="content-cell">
                                        <!-- <h4 style="text-align: center;">New Inquiry</h4> -->

                                        <p>Dear Admin,</p>

                                        <p>We have a new inquiry from {{ $name }}.</p>
                                        <p>These are what we received: </p>
                                        <ul>
                                            <li>Name: {{ $name }}</li>
                                            <li>Email: {{ $email }}</li>
                                            <li>Contact: {{ $contact }}</li>
                                            <li>Message : {{ $message }}</li>
                                        </ul>

                                        
                                        <p>Regards,<br/>1BITS Team</p>

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
                                    &copy; 1Bataan Integrated Transport System. All rights reserved.
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
