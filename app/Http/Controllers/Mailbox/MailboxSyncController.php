<?php

namespace App\Http\Controllers\Mailbox;

use App\Jobs\SyncMailboxAccountJob;
use App\Services\Mailbox\MailboxAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MailboxSyncController extends MailboxController
{
    public function store(Request $request, MailboxAccountService $accounts): RedirectResponse
    {
        $account = $this->mailboxAccount($request, $accounts);
        $this->authorize('sync', $account);

        SyncMailboxAccountJob::dispatch($account);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Mailbox sync queued.'),
        ]);

        return back();
    }
}
