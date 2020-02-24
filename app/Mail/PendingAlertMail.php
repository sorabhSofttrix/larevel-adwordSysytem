<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PendingAlertMail extends Mailable
{
    use Queueable, SerializesModels;
    public $alert;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($alertdata)
    {
        $this->alert = $alertdata;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.alerts.not-resolved')
            ->with([
                'alertdata' =>  $this->alert ,
            ]);
    }
}
