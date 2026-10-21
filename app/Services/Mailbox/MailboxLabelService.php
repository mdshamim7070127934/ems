<?php

namespace App\Services\Mailbox;

use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailLabel;
use App\Models\User;
use Illuminate\Support\Str;

class MailboxLabelService
{
    public function create(EmailAccount $account, User $user, string $name, ?string $color = null): EmailLabel
    {
        $base = Str::slug($name) ?: 'label';
        $slug = $base;
        $suffix = 1;

        while (EmailLabel::query()->where('account_id', $account->id)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return EmailLabel::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'name' => $name,
            'slug' => $slug,
            'color' => $color,
        ]);
    }

    public function rename(EmailLabel $label, string $name, ?string $color = null): EmailLabel
    {
        $label->update([
            'name' => $name,
            'color' => $color,
        ]);

        return $label;
    }

    public function assignToEmail(Email $email, EmailLabel $label): void
    {
        $email->labels()->syncWithoutDetaching([$label->id]);
    }

    public function removeFromEmail(Email $email, EmailLabel $label): void
    {
        $email->labels()->detach($label->id);
    }

    /**
     * @param  list<int>  $threadIds
     */
    public function assignToThreads(EmailAccount $account, array $threadIds, EmailLabel $label): void
    {
        $emailIds = Email::query()
            ->where('account_id', $account->id)
            ->whereIn('thread_id', $threadIds)
            ->pluck('id');

        foreach ($emailIds as $emailId) {
            $email = new Email;
            $email->id = $emailId;
            $email->labels()->syncWithoutDetaching([$label->id]);
        }
    }

    public function delete(EmailLabel $label): void
    {
        $label->delete();
    }
}
