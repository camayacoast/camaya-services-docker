<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\User;

class ResetUserPassword extends Mailable
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

        return $this->subject('Reset User Password')
                    ->from('services@camayacoast.com', 'Camaya Services')
                    ->with([
                        'user' => $this->user,
                        'new_password' => $this->new_password
                    ])
                    ->markdown('emails.admin.reset_user_password');
    }
}
