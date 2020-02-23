<?php


namespace App\Services;

use \Spatie\WebhookClient\ProcessWebhookJob;

class TwakToWebhookProessor extends ProcessWebhookJob
{
    public function handle()
    {
        // $this->webhookCall // contains an instance of `WebhookCall`

        // perform the work here

        dd($this->webhookCall);
    }
}