<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailNotificator extends Mailable
{
    use Queueable, SerializesModels;

    private $title;
    private $msg;
    private $registrationlink;
    private $notificationtemplate;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($title, $msg, $registrationlink, $notificationtemplate)
    {
        $this->title = $title;
        $this->msg = $msg;
        $this->registrationlink = $registrationlink;
        $this->notificationtemplate = $notificationtemplate;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {


        return $this->view($this->notificationtemplate)->with(['title'=>$this->title, 'msg'=>$this->msg, 'registrationlink'=>$this->registrationlink]);
    }
}
