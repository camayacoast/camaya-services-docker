@component('mail::golf_message')
# Thank you for your membership application!

# Reference #: {{$transactions[0]->transaction_id}}

Expires at: {{$transactions[0]->expires_at}}

Kindly settle the amount within <strong>24 hours</strong> from this transaction to confirm your membership and send the following details to <a href="mailto:golfmembership@camayacoast.com">golfmembership@camayacoast.com</a>. 
1. Proof of Payment
2. Member Name

<strong>BANK DETAILS:</strong>

Bank: Metrobank – Rockwell Branch

Account Name: Earth and Shore Tourism Landholdings Corporation.

Account #: 442-7-44201632-8

<h3>Total Amount: P{{number_format($total_amount)}}</h3>


For further assistance/inquiries, please email us at golfmembership@camayacoast.com


Thanks,<br>
Camaya Golf Team
@endcomponent
