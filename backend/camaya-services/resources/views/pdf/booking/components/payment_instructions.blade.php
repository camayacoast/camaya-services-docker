<div>
    <h3 style="text-align: center;">PAYMENT INSTRUCTIONS</h3>

    <p><strong>PAYPAL / CREDIT CARD / DEBIT CARD</strong></p>

    <p>1. Please click the provided link below to proceed with your payment via credit card/debit card:</p>

    <a href="{{env('APP_URL')}}/api/booking/public/payment/paypal/request/{{$booking['reference_number']}}" target="_blank">{{env('APP_URL')}}/api/booking/public/payment/paypal/request/{{$booking['reference_number']}}</a>

    <p><strong>MAYA / GCASH / CREDIT CARD / DEBIT CARD</strong></p>

    <p>1. Please click the provided link below to proceed with your payment via credit card/debit card:</p>

    <a href="{{env('APP_URL')}}/api/booking/public/payment/paymaya/request/{{$booking['reference_number']}}" target="_blank">{{env('APP_URL')}}/api/booking/public/payment/paymaya/request/{{$booking['reference_number']}}</a>

    <p>2. Once payment is successful, booking confirmation with <strong>CONFIRMED</strong> status will be sent to your registered email. </p>

    {{-- Hide bank details request as of April 5, 2023 --}}
    {{-- <p style="margin-top: 30px;"><strong>METROBANK BANK DEPOSIT / ONLINE TRANSFER</strong></p>

    <p><strong>Step 1: </strong>Full payment must be settled to the bank account below:<br/><br/>
        <strong>Metrobank – Rockwell Branch</strong><br/>
        Account number: <strong>442-7-44201632-8</strong><br/>
        Account name: <strong>Earth and Shore Tourism Landholdings Corp.</strong><br/>
    </p>

    <p><strong>Step 2: </strong>Send the scanned image or photo of the deposit, online bank transfer slips, or any proof of payment at <a href="mailto:bankpayments@camayacoast.com">bankpayments@camayacoast.com</a> with the following details:<br/><br/>
        <strong style="font-size: 14px">
            Name of the registered customer<br/>
            Booking reference number<br/>
            Date of visit<br/>
            Date of deposit or payment<br/>
            Metrobank branch<br/>
        </strong>
    </p>

    <p><strong>Step 3: </strong>Wait for the confirmation email with QR Code once payment is verified within 24 – 36 hours after sending of the proof of payment. Present the confirmation upon arrival at the resort/ferry terminal.</p> --}}
    
    <p>
        Kindly settle within 48 hours upon receiving payment details to avoid auto cancellation of your booking.<span style="color:red;">*Bank charges may apply upon using online transfer.</span>
    </p>

    @if ($booking['auto_cancel_at'])
        <p>Your booking will expire on <strong>{{date('F j, Y, D, g:i A', strtotime($booking['auto_cancel_at']))}}</strong>
    @endif

    <p>
        Reservations Email: <a href="mailto:reservations@camayacoast.com">reservations@camayacoast.com</a><br/><br/>
        
        <strong>Commercial Booking Reservations:</strong><br/>
        <!-- 0917 711 2638<br/>
        0917 543 0560<br/><br/> -->
        reservations@camayacoast.com<br/>

        <strong>Property Owner/HOA Booking Reservations:</strong><br/>
        {{-- 0917-520-6192<br/> --}}
        hoabooking@camayacoast.com<br/><br/>

        {{-- Email content: Pending Status --}}
    </p>

</div>
