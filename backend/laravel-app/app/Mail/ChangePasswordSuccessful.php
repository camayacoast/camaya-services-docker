<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\User;

class ChangePasswordSuccessful extends Mailable
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
        return $this->subject('Camaya Online Payment - Change Password Successful')
                    ->from('online.payment@camayacoast.com', 'Camaya Online Payment')
                    // ->bcc(env('APP_ENV') == 'production' ? 'online.payment@camayacoast.com' : 'kit.seno@camayacoast.com')
                    // ->with([
                    //     'user' => $this->user,
                    // ])
                    ->markdown('emails.user.change_password_successful');
    }
}
