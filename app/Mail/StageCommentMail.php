<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class StageCommentMail extends Mailable
{
    use Queueable, SerializesModels;
    public $comment;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($commentData)
    {
        $this->comment = $commentData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.setupstages.comment')
            ->with([
                'commentData' =>  $this->comment ,
            ]);
    }
}
