<?php

namespace App\Http\Controllers\Mailbox;

use App\Http\Requests\Mailbox\StoreMailboxLabelRequest;
use App\Http\Requests\Mailbox\UpdateMailboxLabelRequest;
use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailLabel;
use App\Services\Mailbox\MailboxAccountService;
use App\Services\Mailbox\MailboxLabelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MailboxLabelController extends MailboxController
{
    public function store(
        StoreMailboxLabelRequest $request,
        MailboxAccountService $accounts,
        MailboxLabelService $labels,
    ): RedirectResponse {
        $account = $this->mailboxAccount($request, $accounts);
        $this->authorize('update', $account);

        $labels->create(
            $account,
            $this->user($request),
            $request->string('name')->toString(),
            $request->input('color'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Label created.'),
        ]);

        return back();
    }

    public function update(
        UpdateMailboxLabelRequest $request,
        EmailLabel $mailboxLabel,
        MailboxLabelService $labels,
    ): RedirectResponse {
        $color = $mailboxLabel->color;

        if ($request->exists('color')) {
            $input = $request->input('color');
            $color = is_string($input) && $input !== '' ? $input : null;
        }

        $labels->rename(
            $mailboxLabel,
            $request->string('name')->toString(),
            $color,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Label updated.'),
        ]);

        return back();
    }

    public function assign(Request $request, MailboxLabelService $labels): RedirectResponse
    {
        $request->validate([
            'email_id' => ['required', 'integer', 'exists:emails,id'],
            'label_id' => ['required', 'integer', 'exists:email_labels,id'],
        ]);

        $email = Email::query()->with('account')->findOrFail($request->integer('email_id'));
        $label = EmailLabel::query()->with('account')->findOrFail($request->integer('label_id'));

        $this->authorize('update', $email);
        $this->authorize('update', $label);

        $labels->assignToEmail($email, $label);

        return back();
    }

    public function remove(Request $request, MailboxLabelService $labels): RedirectResponse
    {
        $request->validate([
            'email_id' => ['required', 'integer', 'exists:emails,id'],
            'label_id' => ['required', 'integer', 'exists:email_labels,id'],
        ]);

        $email = Email::query()->with('account')->findOrFail($request->integer('email_id'));
        $label = EmailLabel::query()->findOrFail($request->integer('label_id'));

        $this->authorize('update', $email);
        $this->authorize('update', $label);

        $labels->removeFromEmail($email, $label);

        return back();
    }

    public function assignToThreads(Request $request, MailboxAccountService $accounts, MailboxLabelService $labels): RedirectResponse
    {
        $request->validate([
            'thread_ids' => ['required', 'array', 'min:1'],
            'thread_ids.*' => ['integer', 'exists:email_threads,id'],
            'label_id' => ['required', 'integer', 'exists:email_labels,id'],
        ]);

        $account = $this->mailboxAccount($request, $accounts);
        $label = EmailLabel::query()->findOrFail($request->integer('label_id'));
        $this->authorize('update', $account);
        $this->authorize('update', $label);

        $threadIds = [];

        foreach ($request->array('thread_ids') as $threadId) {
            $threadIds[] = (int) $threadId;
        }

        $labels->assignToThreads($account, $threadIds, $label);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Label assigned.'),
        ]);

        return back();
    }

    public function destroy(EmailLabel $mailboxLabel, MailboxLabelService $labels): RedirectResponse
    {
        $this->authorize('delete', $mailboxLabel);

        $labels->delete($mailboxLabel);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Label deleted.'),
        ]);

        return back();
    }
}
