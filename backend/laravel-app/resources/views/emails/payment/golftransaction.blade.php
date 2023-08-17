@component('mail::golf_message')
# Payment Confirmation

@component('mail::panel')
Transaction ID: {{$transactions[0]->transaction_id}}

Payment Channel: {{$transactions[0]->payment_channel}}

Payment Code: {{$transactions[0]->payment_code}}

Total Amount: P{{number_format($total_amount)}}
@endcomponent

Name: {{$user->first_name}} {{$user->last_name}}

Thank you for your payment!
@component('mail::table')
| Item Transaction ID | Item | Amount |
| :---------------:| ----:| ------:|
@foreach($transactions as $transaction)
| {{$transaction->item_transaction_id}} | {{$transaction->item}} | P{{number_format($transaction->amount)}} |
@endforeach
|  | Total Amount | P{{number_format($total_amount)}} |
@endcomponent

@component('mail::button', ['url' => env('CAMAYA_GOLF_PORTAL')])
Pay another
@endcomponent




Thanks,<br>
<img src="{{ env('APP_URL').'/images/CG_logo_green.png' }}" style="display: block;" width='100' />
Camaya Golf Team
@endcomponent
