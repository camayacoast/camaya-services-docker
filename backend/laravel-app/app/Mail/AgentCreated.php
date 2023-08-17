<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\User;

class AgentCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $user;
    protected $password;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $password)
    {
        //
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Agent Account Created')
                    ->from('services@camayacoast.com', 'Camaya Services')
                    ->with([
                        'user' => $this->user,
                        'password' => $this->password
                    ])
                    ->markdown('emails.agent.created');
    }
}
