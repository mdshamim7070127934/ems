<?php

namespace App\Exceptions;

use App\Enums\Master\EmailProvider;
use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class EmailProviderException extends Exception implements ShouldntReport
{
    public function __construct(
        string $message,
        public readonly string $redirectRoute = 'master.email-accounts.index',
    ) {
        parent::__construct($message);
    }

    public static function notConfigured(EmailProvider $provider): self
    {
        return new self(sprintf(
            '%s OAuth is not configured. Add the client ID and secret to the environment file.',
            $provider->label(),
        ), 'master.email-accounts.connect');
    }

    public static function unsupported(): self
    {
        return new self('The selected email provider does not support OAuth.', 'master.email-accounts.connect');
    }

    public static function invalidState(): self
    {
        return new self(
            'The OAuth session is invalid or has expired. Start the connection again.',
            'master.email-accounts.connect',
        );
    }

    public static function cancelled(): self
    {
        return new self('Account authorization was cancelled.', 'master.email-accounts.connect');
    }

    public static function authorizationFailed(): self
    {
        return new self(
            'The email provider could not complete authorization. Try connecting again.',
            'master.email-accounts.connect',
        );
    }

    public static function tokenExchangeFailed(): self
    {
        return new self(
            'Could not complete the email provider authorization. Try connecting again.',
            'master.email-accounts.connect',
        );
    }

    public static function identityMissing(): self
    {
        return new self(
            'The email provider did not return an account email address.',
            'master.email-accounts.connect',
        );
    }

    public static function alreadyConnected(string $accountName): self
    {
        return new self(sprintf(
            'This email account is already connected as %s.',
            $accountName,
        ));
    }

    public static function reconnectRequired(): self
    {
        return new self('This email account needs to be reconnected.');
    }

    public static function tokenRefreshFailed(): self
    {
        return new self('Could not refresh the email provider access token. Reconnect the account.');
    }

    public static function smtpAccount(): self
    {
        return new self('SMTP accounts do not use OAuth.');
    }

    public static function sendFailed(): self
    {
        return new self('Could not send the email through the configured account.');
    }

    public function render(): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => $this->getMessage(),
        ]);

        return redirect()->route($this->redirectRoute);
    }
}
