@component('mail::golf_message')

Dear {{$user->first_name}},

## You're one-step away to login to your account

Thank you for registering. To login to your Camaya Golf Payment Portal account, please click the activation link below.

@component('mail::button', ['url' => $url])
Activate my account
@endcomponent

Thanks,<br>
<img src="{{ env('APP_URL').'/images/CG_logo_green.png' }}" style="display: block;" width='100' />
Camaya Golf Team
@endcomponent
