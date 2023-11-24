<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable {

    public $content;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($content) {
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this->markdown('email.invitation-mail')
                        ->from(env('MAIL_FROM_ADDRESS','support@bookmyinfluencers.com'),'BookMyInfluencers Team')
                        ->subject('New Invitataion Recieved!')
                        ->with($this->content);
    }

}
