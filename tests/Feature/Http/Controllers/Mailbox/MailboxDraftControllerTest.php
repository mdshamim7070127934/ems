<?php

use App\Jobs\SendMailboxEmailJob;
use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailDraft;
use App\Models\Mailbox\EmailThread;
use App\Models\Mailbox\UniqueEmail;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('saving a draft and sending queues the mailbox send job', function () {
    Queue::fake([SendMailboxEmailJob::class]);

    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'to_emails' => ['to@example.com'],
            'subject' => 'Hello',
            'body_html' => '<p>Hi</p>',
            'send' => '1',
        ])
        ->assertRedirect(route('mailbox.index', ['account_id' => $account->id]));

    Queue::assertPushedOn((string) config('mailbox.queues.send'), SendMailboxEmailJob::class);
    expect(EmailDraft::query()->count())->toBe(1);
});

test('sending a draft from the drafts page queues the job', function () {
    Queue::fake([SendMailboxEmailJob::class]);

    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
        'to_emails' => ['to@example.com'],
        'subject' => 'Hello',
    ]);

    $this->actingAs($user)
        ->post(route('mailbox.drafts.send', $draft))
        ->assertRedirect(route('mailbox.index'));

    Queue::assertPushedOn(
        (string) config('mailbox.queues.send'),
        SendMailboxEmailJob::class,
        fn (SendMailboxEmailJob $job): bool => $job->draft->is($draft),
    );
});

test('sending a compose draft without recipients is rejected', function () {
    Queue::fake([SendMailboxEmailJob::class]);

    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'subject' => 'Hello',
            'body_html' => '<p>Hi</p>',
            'send' => '1',
        ])
        ->assertSessionHasErrors('to_emails');

    Queue::assertNothingPushed();
    expect(EmailDraft::query()->count())->toBe(0);
});

test('deleting a draft redirects to the drafts list and removes the record', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
    ]);

    $this->actingAs($user)
        ->from(route('mailbox.drafts', ['account_id' => $account->id]))
        ->delete(route('mailbox.drafts.destroy', $draft))
        ->assertRedirect(route('mailbox.drafts', ['account_id' => $account->id]));

    $this->assertModelMissing($draft);
});

test('a user cannot delete another users draft', function () {
    $user = User::factory()->create();
    EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $other = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->create(['user_id' => $other->id]);
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
    ]);

    $this->actingAs($user)
        ->delete(route('mailbox.drafts.destroy', $draft))
        ->assertForbidden();

    $this->assertModelExists($draft);
});

test('saving an existing draft updates the same record', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
        'subject' => 'Original',
        'body_html' => '<p>Old</p>',
    ]);

    $this->actingAs($user)
        ->postJson(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'draft_id' => $draft->id,
            'to_emails' => ['to@example.com'],
            'subject' => 'Updated subject',
            'body_html' => '<p>New body</p>',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $draft->id)
        ->assertJsonPath('data.subject', 'Updated subject');

    expect(EmailDraft::query()->count())->toBe(1)
        ->and($draft->fresh()?->body_html)->toBe('<p>New body</p>');
});

test('saving a draft twice with the returned id does not create a second record', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $first = $this->actingAs($user)
        ->postJson(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'to_emails' => ['to@example.com'],
            'subject' => 'First save',
            'body_html' => '<p>One</p>',
        ])
        ->assertOk()
        ->json('data.id');

    $this->actingAs($user)
        ->postJson(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'draft_id' => $first,
            'to_emails' => ['to@example.com'],
            'subject' => 'Second save',
            'body_html' => '<p>Two</p>',
        ])
        ->assertOk()
        ->assertJsonPath('data.id', $first)
        ->assertJsonPath('data.subject', 'Second save');

    expect(EmailDraft::query()->count())->toBe(1);
});

test('json send queues the mailbox job without creating extra drafts', function () {
    Queue::fake([SendMailboxEmailJob::class]);

    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'to_emails' => ['to@example.com'],
            'subject' => 'Send me',
            'body_html' => '<p>Hi</p>',
            'send' => true,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    Queue::assertPushedOn((string) config('mailbox.queues.send'), SendMailboxEmailJob::class);
    expect(EmailDraft::query()->count())->toBe(1);
});

test('autosaving a draft returns json and does not queue send', function () {
    Queue::fake([SendMailboxEmailJob::class]);

    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'to_emails' => ['to@example.com'],
            'subject' => 'Autosave',
            'body_html' => '<p>Draft</p>',
            'autosave' => true,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.subject', 'Autosave');

    Queue::assertNothingPushed();
    expect(EmailDraft::query()->count())->toBe(1);
});

test('recipient autocomplete returns matching unique emails', function () {
    $user = User::factory()->create();
    EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    UniqueEmail::factory()->create(['email_id' => 'jane.doe@example.com']);

    $this->actingAs($user)
        ->getJson(route('mailbox.autocomplete', ['q' => 'jane']))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.email', 'jane.doe@example.com')
        ->assertJsonPath('data.0.display_name', 'jane.doe');
});

test('compose prefill returns a reply draft payload', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);
    $email = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'from_email' => 'sender@example.com',
        'from_name' => 'Sender',
        'subject' => 'Hello',
        'body_html' => '<p>Hi</p>',
        'is_draft' => false,
    ]);

    $this->actingAs($user)
        ->getJson(route('mailbox.compose.prefill', [
            'reply' => $email->id,
            'account_id' => $account->id,
        ]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.to_emails.0', 'sender@example.com')
        ->assertJsonPath('data.subject', 'Re: Hello');
});

test('a user cannot prefill a reply to another users email', function () {
    $user = User::factory()->create();
    EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $other = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->create(['user_id' => $other->id]);
    $thread = EmailThread::factory()->create(['account_id' => $account->id]);
    $email = Email::factory()->create([
        'account_id' => $account->id,
        'thread_id' => $thread->id,
        'is_draft' => false,
    ]);

    $this->actingAs($user)
        ->getJson(route('mailbox.compose.prefill', ['reply' => $email->id]))
        ->assertForbidden();
});

test('saving a draft a second time persists the resized image html', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $smallBody = '<p><img src="https://example.com/photo.jpg" alt="Photo" style="max-width:200px !important; width:auto !important; height:auto !important;" data-img-size="small"></p>';
    $originalBody = '<p><img src="https://example.com/photo.jpg" alt="Photo" style="max-width:none !important; width:auto !important; height:auto !important;" data-img-size="original"></p>';

    $draftId = $this->actingAs($user)
        ->postJson(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'to_emails' => ['to@example.com'],
            'subject' => 'Image draft',
            'body_html' => $smallBody,
        ])
        ->assertOk()
        ->assertJsonPath('data.body_html', $smallBody)
        ->json('data.id');

    $this->actingAs($user)
        ->postJson(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'draft_id' => $draftId,
            'to_emails' => ['to@example.com'],
            'subject' => 'Image draft',
            'body_html' => $originalBody,
        ])
        ->assertOk()
        ->assertJsonPath('data.id', $draftId)
        ->assertJsonPath('data.body_html', $originalBody);

    $draft = EmailDraft::query()->find($draftId);

    expect($draft?->body_html)->toBe($originalBody);
});

test('fetching a draft returns the latest saved image size html', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
        'subject' => 'Image draft',
        'body_html' => '<p><img src="https://example.com/photo.jpg" alt="Photo" data-img-size="small"></p>',
    ]);
    $originalBody = '<p><img src="https://example.com/photo.jpg" alt="Photo" style="max-width:none !important; width:auto !important; height:auto !important;" data-img-size="original"></p>';

    $this->actingAs($user)
        ->postJson(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'draft_id' => $draft->id,
            'to_emails' => ['to@example.com'],
            'subject' => 'Image draft',
            'body_html' => $originalBody,
        ])
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('mailbox.drafts.show', $draft))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $draft->id)
        ->assertJsonPath('data.body_html', $originalBody);
});

test('the drafts list includes the latest body html after a second save', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $originalBody = '<p><img src="https://example.com/photo.jpg" alt="Photo" data-img-size="original"></p>';
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
        'subject' => 'Image draft',
        'body_html' => '<p><img src="https://example.com/photo.jpg" alt="Photo" data-img-size="small"></p>',
    ]);

    $this->actingAs($user)
        ->postJson(route('mailbox.drafts.store'), [
            'account_id' => $account->id,
            'draft_id' => $draft->id,
            'to_emails' => ['to@example.com'],
            'subject' => 'Image draft',
            'body_html' => $originalBody,
        ])
        ->assertOk();

    $this->actingAs($user)
        ->get(route('mailbox.drafts', ['account_id' => $account->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/drafts')
            ->where('drafts.data.0.body_html', $originalBody)
            ->where('drafts.data.0.id', $draft->id));
});

test('compose with a draft query returns the stored image html', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $body = '<p><img src="https://example.com/photo.jpg" alt="Photo" data-img-size="original"></p>';
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
        'subject' => 'Image draft',
        'body_html' => $body,
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.compose', ['draft' => $draft->id, 'account_id' => $account->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/compose')
            ->where('draft.id', $draft->id)
            ->where('draft.body_html', $body));
});

test('guests cannot view a draft', function () {
    $draft = EmailDraft::factory()->create();

    $this->getJson(route('mailbox.drafts.show', $draft))
        ->assertUnauthorized();
});

test('a user cannot view another users draft', function () {
    $user = User::factory()->create();
    EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    $other = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->create(['user_id' => $other->id]);
    $draft = EmailDraft::factory()->create([
        'account_id' => $account->id,
    ]);

    $this->actingAs($user)
        ->getJson(route('mailbox.drafts.show', $draft))
        ->assertForbidden();
});

test('the drafts list returns 25 records per page by default', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    EmailDraft::factory()->count(26)->create(['account_id' => $account->id]);

    $this->actingAs($user)
        ->get(route('mailbox.drafts', ['account_id' => $account->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/drafts')
            ->has('drafts.data', 25)
            ->where('drafts.meta.per_page', 25)
            ->where('drafts.meta.total', 26)
            ->where('drafts.meta.current_page', 1)
            ->where('filters.pageSize', 25));
});

test('the drafts list second page returns the remaining records', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    EmailDraft::factory()->count(26)->create(['account_id' => $account->id]);

    $this->actingAs($user)
        ->get(route('mailbox.drafts', ['account_id' => $account->id, 'page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/drafts')
            ->has('drafts.data', 1)
            ->where('drafts.meta.current_page', 2)
            ->where('drafts.meta.last_page', 2)
            ->where('drafts.meta.total', 26));
});

test('drafts page size cannot exceed the allowed maximum', function () {
    $user = User::factory()->create();
    EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('mailbox.drafts', ['pageSize' => 1000]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.pageSize', 25)
            ->where('drafts.meta.per_page', 25));
});

test('searching drafts stays on the drafts page and returns matching records', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    EmailDraft::factory()->create([
        'account_id' => $account->id,
        'subject' => 'Flight itinerary',
        'to_emails' => ['alice@example.com'],
        'body_plain' => 'See attached',
    ]);
    EmailDraft::factory()->create([
        'account_id' => $account->id,
        'subject' => 'Unrelated note',
        'to_emails' => ['bob@example.com'],
        'body_plain' => 'Hello',
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.drafts', [
            'account_id' => $account->id,
            'search' => 'itinerary',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/drafts')
            ->has('drafts.data', 1)
            ->where('drafts.data.0.subject', 'Flight itinerary')
            ->where('filters.search', 'itinerary'));
});

test('searching drafts by recipient email returns the matching draft', function () {
    $user = User::factory()->create();
    $account = EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    EmailDraft::factory()->create([
        'account_id' => $account->id,
        'subject' => 'Quote for Alice',
        'to_emails' => ['alice@example.com'],
    ]);
    EmailDraft::factory()->create([
        'account_id' => $account->id,
        'subject' => 'Quote for Bob',
        'to_emails' => ['bob@example.com'],
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.drafts', [
            'account_id' => $account->id,
            'search' => 'alice@example.com',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mailbox/drafts')
            ->has('drafts.data', 1)
            ->where('drafts.data.0.subject', 'Quote for Alice')
            ->where('filters.search', 'alice@example.com'));
});
