<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\User;

class GolfChangePasswordSuccessful extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        //
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Camaya Golf Payment - Change Password Successful')
                    ->from('golfmembership@camayacoast.com', 'Camaya Golf Payment')
                    // ->bcc(env('APP_ENV') == 'production' ? 'online.payment@camayacoast.com' : 'kit.seno@camayacoast.com')
                    // ->with([
                    //     'user' => $this->user,
                    // ])
                    ->markdown('emails.user.golf_change_password_successful');
    }
}
