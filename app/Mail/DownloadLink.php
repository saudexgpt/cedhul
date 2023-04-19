<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;


class DownloadLink extends Mailable
{
    use Queueable, SerializesModels;

    public $download; //make it public so that the view resource can access it
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($download)
    {
        //
        $this->download = $download;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $download = $this->download;
        return $this->view('emails.send_download_link', compact('download'));
    }
}
