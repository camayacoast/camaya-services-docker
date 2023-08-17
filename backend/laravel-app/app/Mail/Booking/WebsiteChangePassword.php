<?php

namespace App\Mail\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\User;

class WebsiteChangePassword extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $new_password;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $new_password)
    {
        //
        $this->user = $user;
        $this->new_password = $new_password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
                ->subject('[PASSWORD CHANGED] Camaya User: '.$this->user->first_name.' '.$this->user->last_name)
                ->from('services@camayacoast.com', 'Camaya Services')
                // ->cc(env('APP_ENV') == 'production' ? 'reservations@camayacoast.com' : 'kit.seno@camayacoast.com')
                ->with([
                        'user' => $this->user,
                        'new_password' => $this->new_password
                ])
                ->markdown('emails.booking.change_password');
    }
}
