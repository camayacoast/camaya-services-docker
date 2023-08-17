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

    <div style="text-align: center;"><img src="images/camaya-logo.png" width="140" /></div>
        <h3 style="text-transform: uppercase; text-align: center;">VOUCHER PURCHASE DETAILS</h3>

        <table style="width: 100%; margin-top: 20px; margin-bottom: 20px;" border="1" bordercolor="#eaeaea" cellpadding="8" cellspacing="0">
            <tr>
                <th>Voucher</th>
                <th>Transaction Reference No.</th>
                <th>Voucher Status</th>
                <th>Payment Status</th>
                <th>Price</th>
            </tr>
            @foreach ($generated_vouchers as $generated_voucher)
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

        <p style="text-align: right; font-weight: 700;">Total Amount: P {{number_format(collect($generated_vouchers)->sum('price'), 2)}}</p>
        
        <div class="page-break-before"></div>
        @include('pdf.booking.components.voucher_payment_instructions', ['transaction_reference_number' => $transaction_reference_number])
    

    </div>
    </body>
</html>

