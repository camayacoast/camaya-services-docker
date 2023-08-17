<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\User;

class UserRegistered extends Mailable
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
        $this->url = $url;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to Camaya Online Payment Portal')
                    ->from('online.payment@camayacoast.com', 'Camaya Online Payment')
                    ->with([
                        'url' => $this->url,
                        'user' => $this->user
                    ])
                    ->markdown('emails.user.registered');
    }
}
