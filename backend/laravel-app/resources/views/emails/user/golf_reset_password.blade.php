@component('mail::golf_message')

Hi {{$user->first_name}},

Do you need to reset your password? Click the link below. It will redirect you to a secure page that will allow you to change your password.

@component('mail::button', ['url' => $url])
Reset Password
@endcomponent

You may ignore this if you didn't try to reset your password.

Thanks,<br>
<img src="{{ env('APP_URL').'/images/CG_logo_green.png' }}" style="display: block;" width='100' />
Camaya Golf Team
@endcomponent
