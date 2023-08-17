@component('mail::layout')
{{-- Header --}}
@slot('header')
<tr>
<td class="header">
<a href="{{ env('CAMAYA_BOOKING_PORTAL_URL') }}" style="display: inline-block;">
<img src="{{ env('APP_URL').'/images/camaya-logo.png' }}" style="vertical-align: middle;" width='100' /> Camaya Booking
</a>
</td>
</tr>
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
@endcomponent
@endslot
@endcomponent
