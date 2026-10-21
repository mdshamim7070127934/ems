<?php

namespace App\Http\Controllers\Mailbox;

use App\Enums\Master\EmailProvider;
use App\Exceptions\EmailProviderException;
use App\Http\Requests\Mailbox\StoreMailboxSmtpAccountRequest;
use App\Jobs\SyncMailboxAccountJob;
use App\Models\Mailbox\EmailAccount;
use App\Services\Mailbox\MailboxAccountService;
use App\Services\Mailbox\MailboxOAuthConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MailboxAccountController extends MailboxController
{
    public function __construct(
        private MailboxOAuthConnectionService $connections,
        private MailboxAccountService $accounts,
    ) {}

    public function connect(): Response
    {
        $this->authorize('create', EmailAccount::class);

        return Inertia::render('mailbox/connect');
    }

    public function createSmtp(): Response
    {
        $this->authorize('create', EmailAccount::class);

        return Inertia::render('mailbox/smtp-form');
    }

    public function storeSmtp(StoreMailboxSmtpAccountRequest $request): RedirectResponse
    {
        $account = $this->accounts->createSmtpImapAccount(
            $this->user($request),
            [
                'email' => $request->string('email')->toString(),
                'display_name' => $request->filled('display_name') ? $request->string('display_name')->toString() : null,
                'smtp_host' => $request->string('smtp_host')->toString(),
                'smtp_port' => $request->integer('smtp_port'),
                'smtp_username' => $request->filled('smtp_username') ? $request->string('smtp_username')->toString() : null,
                'smtp_password' => $request->filled('smtp_password') ? $request->string('smtp_password')->toString() : null,
                'smtp_encryption' => $request->filled('smtp_encryption') ? $request->string('smtp_encryption')->toString() : null,
                'imap_host' => $request->filled('imap_host') ? $request->string('imap_host')->toString() : null,
                'imap_port' => $request->integer('imap_port'),
                'imap_username' => $request->filled('imap_username') ? $request->string('imap_username')->toString() : null,
                'imap_password' => $request->filled('imap_password') ? $request->string('imap_password')->toString() : null,
                'imap_encryption' => $request->filled('imap_encryption') ? $request->string('imap_encryption')->toString() : null,
                'is_default' => $request->boolean('is_default'),
            ],
        );

        SyncMailboxAccountJob::dispatch($account);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('SMTP / IMAP mailbox connected.'),
        ]);

        return to_route('mailbox.index');
    }

    public function makeDefault(Request $request, EmailAccount $mailboxAccount): RedirectResponse
    {
        $this->authorize('update', $mailboxAccount);

        $this->accounts->makeDefault($mailboxAccount);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Default mailbox updated.'),
        ]);

        return back();
    }

    public function destroy(EmailAccount $mailboxAccount): RedirectResponse
    {
        $this->authorize('delete', $mailboxAccount);

        $this->accounts->delete($mailboxAccount);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Mailbox account removed.'),
        ]);

        return to_route('mailbox.connect');
    }

    public function redirect(Request $request, string $provider): SymfonyResponse
    {
        $this->authorize('create', EmailAccount::class);

        return Inertia::location(
            $this->connections->authorizationRedirectUrl($this->user($request), $this->provider($provider)),
        );
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $emailProvider = $this->provider($provider);

        $account = $this->connections->complete(
            $this->user($request),
            $emailProvider,
            is_string($request->query('state')) ? $request->query('state') : null,
            is_string($request->query('code')) ? $request->query('code') : null,
            is_string($request->query('error')) ? $request->query('error') : null,
        );

        SyncMailboxAccountJob::dispatch($account);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':provider mailbox connected.', ['provider' => $emailProvider->label()]),
        ]);

        return to_route('mailbox.index');
    }

    public function reconnect(Request $request, EmailAccount $mailboxAccount): SymfonyResponse
    {
        $this->authorize('update', $mailboxAccount);

        if (! $mailboxAccount->usesOAuth()) {
            throw new EmailProviderException('SMTP / IMAP accounts do not use OAuth.', 'mailbox.connect');
        }

        $provider = EmailProvider::from($mailboxAccount->provider->value);

        return Inertia::location(
            $this->connections->authorizationRedirectUrl($this->user($request), $provider, $mailboxAccount),
        );
    }

    public function disconnect(EmailAccount $mailboxAccount): RedirectResponse
    {
        $this->authorize('update', $mailboxAccount);

        $this->connections->disconnect($mailboxAccount);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Mailbox disconnected.'),
        ]);

        return back();
    }

    private function provider(string $provider): EmailProvider
    {
        $emailProvider = EmailProvider::tryFrom($provider);

        if ($emailProvider === null || ! $emailProvider->usesOAuth()) {
            abort(404);
        }

        return $emailProvider;
    }
}
