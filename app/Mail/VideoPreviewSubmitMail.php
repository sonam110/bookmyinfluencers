<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VideoPreviewSubmitMail extends Mailable
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
        return $this->markdown('email.video-preview-submit-mail')
                        ->subject('Preview Video Submitted by '.@$this->content['channel_name'].'!')
                        ->from(env('MAIL_FROM_ADDRESS','support@bookmyinfluencers.com'),'BookMyInfluencers Team')
                        ->with($this->content);
    }
}
