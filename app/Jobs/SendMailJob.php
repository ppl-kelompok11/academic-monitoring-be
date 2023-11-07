<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $email;
    public $token;

    public $title;
    public $content;
    public $fullname;

    public $link;
    public $subject;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email, $subject, $token, $title, $content, $fullname, $link)
    {
        //
        $this->email = $email;
        $this->token = $token;
        $this->title = $title;
        $this->content = $content;
        $this->fullname = $fullname;
        $this->link = $link;
        $this->subject = $subject;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        Mail::send(
            "email_template",
            [
                "user" => $this->fullname,
                "token" => $this->token,
                "title" => $this->title,
                "content" => $this->content,
                "link" => $this->link,
            ],
            function ($message) {
                $message->to($this->email, $this->email)
                    ->subject($this->subject);
            }
        );
    }
}
