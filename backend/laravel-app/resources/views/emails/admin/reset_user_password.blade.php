@component('mail::message')
# Camaya Web Services

Hi, {{$user['first_name']}}.

Password Reset

Your password has been reset.

New password: {{$new_password}}

<a href="{{env('APP_URL')}}" target="_blank">{{env('APP_URL')}}</a>

Thanks,<br>
Camaya Web Services
@endcomponent
