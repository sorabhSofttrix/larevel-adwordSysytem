<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AccountPendingMail extends Mailable
{
    use Queueable, SerializesModels;
    public $account;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($accountData)
    {
        $this->account = $accountData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.setupstages.pending-campaign-live')
            ->with([
                'accountData' =>  $this->account ,
            ]);
    }
}
