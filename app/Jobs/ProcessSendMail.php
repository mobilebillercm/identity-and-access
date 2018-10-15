<?php

namespace App\Jobs;

use App\Mail\MailNotificator;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class ProcessSendMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $to;
    protected  $mailNotificator;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($to, MailNotificator $mailNotificator)
    {
        $this->to = $to;
        $this->mailNotificator = $mailNotificator;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        Mail::to($this->to)->send($this->mailNotificator);
    }
}
