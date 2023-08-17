@component('mail::message')

Dear {{$user->first_name}},

## You're one-step away to login to your account

Thank you for registering. To login to your Camaya Online Payment Portal account, please click the activation link below.

@component('mail::button', ['url' => $url])
Activate my account
@endcomponent

Thanks,<br>
<img src="{{ env('APP_URL').'/images/camaya-logo.png' }}" style="display: block;" width='100' />
Online Payment Team
@endcomponent
