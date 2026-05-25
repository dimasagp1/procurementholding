<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {email?}', function (?string $email = null) {
    $recipient = $email ?: config('mail.from.address');

    if (! $recipient) {
        $this->error('No recipient email provided and MAIL_FROM_ADDRESS is empty.');
        return self::FAILURE;
    }

    Mail::raw(
        'This is a test email from the Procurement system sent at '.now()->toDateTimeString().'.',
        function ($message) use ($recipient) {
            $message->to($recipient)
                ->subject('Procurement Mail Test');
        }
    );

    $this->info("Test email sent to {$recipient}.");

    return self::SUCCESS;
})->purpose('Send a test email using the current mail configuration');
