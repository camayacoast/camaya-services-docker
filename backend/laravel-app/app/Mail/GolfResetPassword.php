<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\User;

class GolfResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $url)
    {
        //
        $this->user = $user;
        $this->url = $url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Camaya Golf Payment Password Reset')
                    ->from('golfmembership@camayacoast.com', 'Camaya Golf Payment')
                    // ->bcc(env('APP_ENV') == 'production' ? 'online.payment@camayacoast.com' : 'kit.seno@camayacoast.com')
                    ->with([
                        'user' => $this->user,
                        'url' => $this->url,
                    ])->markdown('emails.user.golf_reset_password');
    }
}
