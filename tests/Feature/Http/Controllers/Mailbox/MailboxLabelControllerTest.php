<?php

use App\Enums\Mailbox\MailboxPermission;
use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailFolder;
use App\Models\Mailbox\EmailLabel;
use App\Models\Mailbox\EmailThread;
use App\Models\User;

test('a user can create a label on their mailbox account', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('mailbox.labels.store'), [
            'account_id' => $account->id,
            'name' => 'Follow up',
            'color' => '#22c55e',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('email_labels', [
        'account_id' => $account->id,
        'user_id' => $user->id,
        'name' => 'Follow up',
    ]);
});

test('mailbox permissions are defined for authenticated users', function () {
    $user = User::factory()->create();

    expect($user->can(MailboxPermission::Inbox->value))->toBeTrue()
        ->and($user->can(MailboxPermission::Send->value))->toBeTrue()
        ->and($user->can(MailboxPermission::Sync->value))->toBeTrue();
});

test('a user can assign a label to every email in selected threads', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $folder = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);
    $first = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'is_draft' => false,
    ]);
    $second = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'is_draft' => false,
    ]);
    $label = EmailLabel::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'name' => 'Follow up',
    ]);

    $this->actingAs($user)
        ->post(route('mailbox.labels.assign-threads'), [
            'account_id' => $account->id,
            'label_id' => $label->id,
            'thread_ids' => [$thread->id],
        ])
        ->assertRedirect();

    expect($first->fresh()?->labels()->where('email_labels.id', $label->id)->exists())->toBeTrue()
        ->and($second->fresh()?->labels()->where('email_labels.id', $label->id)->exists())->toBeTrue();
});

test('deleting a label removes it from assigned emails', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $folder = EmailFolder::factory()->inbox()->create(['account_id' => $account->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);
    $email = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'folder_id' => $folder->id,
        'is_draft' => false,
    ]);
    $label = EmailLabel::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'name' => 'Old',
    ]);
    $email->labels()->attach($label->id);

    $this->actingAs($user)
        ->delete(route('mailbox.labels.destroy', $label))
        ->assertRedirect();

    $this->assertDatabaseMissing('email_labels', ['id' => $label->id]);
    $this->assertDatabaseMissing('email_label_mappings', [
        'email_id' => $email->id,
        'label_id' => $label->id,
    ]);
});

test('a user can rename a mailbox label', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $label = EmailLabel::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'name' => 'Follow up',
        'color' => '#1967d2',
    ]);

    $this->actingAs($user)
        ->patch(route('mailbox.labels.update', $label), [
            'name' => 'Urgent',
            'color' => '#d93025',
        ])
        ->assertRedirect();

    expect($label->fresh())
        ->name->toBe('Urgent')
        ->color->toBe('#d93025')
        ->slug->toBe($label->slug);
});

test('renaming a mailbox label without a color keeps the existing color', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $label = EmailLabel::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'name' => 'Follow up',
        'color' => '#1967d2',
    ]);

    $this->actingAs($user)
        ->patch(route('mailbox.labels.update', $label), [
            'name' => 'Urgent',
        ])
        ->assertRedirect();

    expect($label->fresh())
        ->name->toBe('Urgent')
        ->color->toBe('#1967d2');
});

test('renaming a mailbox label can clear its color', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $label = EmailLabel::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'name' => 'Follow up',
        'color' => '#1967d2',
    ]);

    $this->actingAs($user)
        ->patch(route('mailbox.labels.update', $label), [
            'name' => 'Urgent',
            'color' => '',
        ])
        ->assertRedirect();

    expect($label->fresh())
        ->name->toBe('Urgent')
        ->color->toBeNull();
});

test('a user cannot rename another users mailbox label', function () {
    $user = User::factory()->create();
    $label = EmailLabel::factory()->create([
        'name' => 'Private',
    ]);

    $this->actingAs($user)
        ->patch(route('mailbox.labels.update', $label), [
            'name' => 'Stolen',
        ])
        ->assertForbidden();

    expect($label->fresh()?->name)->toBe('Private');
});

test('renaming a mailbox label requires a name', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $label = EmailLabel::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'name' => 'Follow up',
    ]);

    $this->actingAs($user)
        ->from(route('mailbox.index'))
        ->patch(route('mailbox.labels.update', $label), [
            'name' => '',
        ])
        ->assertRedirect(route('mailbox.index'))
        ->assertSessionHasErrors('name');
});

test('a user cannot update another users mailbox account', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->create();

    expect($user->can('update', $account))->toBeFalse();
});
