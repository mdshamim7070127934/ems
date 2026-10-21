<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserRegisteredMail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your :app account is ready', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.user-registered',
            with: [
                'userName' => $this->user->first_name ?: $this->user->name,
                'loginUrl' => route('login'),
            ],
        );
    }
}
