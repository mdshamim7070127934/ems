<?php

namespace App\Http\Controllers\Mailbox;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mailbox\MailboxAccountResource;
use App\Http\Resources\Mailbox\MailboxFolderResource;
use App\Http\Resources\Mailbox\MailboxLabelResource;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailFolder;
use App\Models\User;
use App\Services\Mailbox\MailboxAccountService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class MailboxController extends Controller
{
    use AuthorizesRequests;

    protected function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }

    protected function mailboxAccount(Request $request, MailboxAccountService $accounts): EmailAccount
    {
        $user = $this->user($request);
        $accountId = $request->integer('account_id');

        $account = $accountId > 0
            ? EmailAccount::query()->ownedBy($user)->whereKey($accountId)->first()
            : $accounts->defaultFor($user);

        if ($account === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $this->authorize('view', $account);

        return $account;
    }

    /**
     * Shared Gmail chrome (accounts, folders, labels) for mailbox pages.
     *
     * @return array<string, mixed>
     */
    protected function mailboxChrome(Request $request, EmailAccount $account): array
    {
        $accounts = EmailAccount::query()
            ->ownedBy($this->user($request))
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        $folders = EmailFolder::query()
            ->where('account_id', $account->id)
            ->orderBy('name')
            ->get();

        return [
            'accounts' => MailboxAccountResource::collection($accounts)->resolve(),
            'folders' => MailboxFolderResource::collection($folders)->resolve(),
            'labels' => MailboxLabelResource::collection($account->labels()->orderBy('name')->get())->resolve(),
        ];
    }
}
