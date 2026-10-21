<?php

namespace App\Http\Controllers\Master;

use App\Enums\Master\EmailProvider;
use App\Exceptions\EmailProviderException;
use App\Http\Controllers\Controller;
use App\Models\Master\EmailAccount;
use App\Models\User;
use App\Services\Mail\OAuth\OAuthConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EmailAccountOAuthController extends Controller
{
    public function __construct(
        private OAuthConnectionService $connections,
    ) {}

    /**
     * Show the Connect Account provider selection page.
     */
    public function create(): Response
    {
        return Inertia::render('master/email-accounts/connect');
    }

    /**
     * Redirect the authenticated user to the provider authorization page.
     */
    public function redirect(Request $request, string $provider): SymfonyResponse
    {
        $emailProvider = $this->provider($provider);

        return Inertia::location(
            $this->connections->authorizationRedirectUrl($this->user($request), $emailProvider),
        );
    }

    /**
     * Handle the OAuth authorization callback.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $emailProvider = $this->provider($provider);
        $state = $request->query('state');
        $code = $request->query('code');
        $error = $request->query('error');

        $account = $this->connections->complete(
            $this->user($request),
            $emailProvider,
            is_string($state) ? $state : null,
            is_string($code) ? $code : null,
            is_string($error) ? $error : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $account->wasRecentlyCreated
                ? __(':provider account connected.', ['provider' => $emailProvider->label()])
                : __(':provider account reconnected.', ['provider' => $emailProvider->label()]),
        ]);

        return $account->wasRecentlyCreated
            ? to_route('master.email-accounts.edit', $account)
            : to_route('master.email-accounts.show', $account);
    }

    /**
     * Start OAuth again for an existing Gmail or Outlook account.
     */
    public function reconnect(Request $request, EmailAccount $emailAccount): SymfonyResponse
    {
        if (! $emailAccount->usesOAuth()) {
            throw EmailProviderException::smtpAccount();
        }

        return Inertia::location(
            $this->connections->authorizationRedirectUrl(
                $this->user($request),
                $emailAccount->provider,
                $emailAccount,
            ),
        );
    }

    /**
     * Revoke stored OAuth tokens and mark the account for reconnection.
     */
    public function disconnect(EmailAccount $emailAccount): RedirectResponse
    {
        $this->connections->disconnect($emailAccount);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Email account disconnected.'),
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

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
