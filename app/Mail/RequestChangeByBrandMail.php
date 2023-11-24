<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestChangeByBrandMail extends Mailable
{
    use Queueable, SerializesModels;

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
        return $this->markdown('email.request-changeby-brand-mail')
                        ->subject(''.@$this->content['order']->campInfo->brand_name.' has requested changes in '.@$this->content['title'].'!')
                         ->from(env('MAIL_FROM_ADDRESS','support@bookmyinfluencers.com'),'BookMyInfluencers Team')
                        ->with($this->content);
    }
        
}
