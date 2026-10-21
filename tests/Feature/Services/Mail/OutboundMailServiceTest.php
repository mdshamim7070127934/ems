<?php

use App\Exceptions\EmailProviderException;
use App\Mail\UserRegisteredMail;
use App\Models\Master\EmailAccount;
use App\Models\Master\EmailType;
use App\Models\User;
use App\Services\Mail\OutboundMailService;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

test('registration mail is skipped when no account is assigned to the type', function () {
    Event::fake([MessageLogged::class]);
    Mail::fake();
    Http::preventStrayRequests();

    $user = User::factory()->create(['email' => 'jane@example.com']);

    app(OutboundMailService::class)->sendForType(
        EmailType::CODE_REGISTRATION,
        new UserRegisteredMail($user),
        $user->email,
    );

    Mail::assertNothingSent();
    Event::assertDispatched(
        MessageLogged::class,
        fn (MessageLogged $event): bool => $event->level === 'error'
            && str_contains($event->message, 'No email account is assigned'),
    );
});

test('the default registration account is preferred when several are assigned', function () {
    Mail::fake();

    $type = EmailType::factory()->registration()->create();
    $other = EmailAccount::factory()->create(['from_email' => 'other@example.com']);
    $default = EmailAccount::factory()->default()->create(['from_email' => 'default@example.com']);
    $other->syncEmailTypes([$type->id]);
    $default->syncEmailTypes([$type->id]);

    $user = User::factory()->create(['email' => 'jane@example.com']);

    app(OutboundMailService::class)->sendForType(
        EmailType::CODE_REGISTRATION,
        new UserRegisteredMail($user),
        $user->email,
    );

    Mail::assertSent(
        UserRegisteredMail::class,
        fn (UserRegisteredMail $mail): bool => collect($mail->from)->contains(
            fn (array $from): bool => ($from['address'] ?? null) === 'default@example.com',
        ),
    );
});

test('gmail registration mail is sent through the gmail api', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response(['id' => 'msg-1'], 200),
    ]);

    $type = EmailType::factory()->registration()->create();
    $account = EmailAccount::factory()->gmailOAuth()->create([
        'from_email' => 'noreply@gmail.com',
    ]);
    $account->syncEmailTypes([$type->id]);

    $user = User::factory()->create(['email' => 'jane@example.com']);

    app(OutboundMailService::class)->sendForType(
        EmailType::CODE_REGISTRATION,
        new UserRegisteredMail($user),
        $user->email,
    );

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send'
        && $request->hasHeader('Authorization', 'Bearer oauth-access-token')
        && is_string($request['raw'])
        && $request['raw'] !== '');
});

test('outlook registration mail is sent through microsoft graph', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://graph.microsoft.com/v1.0/me/sendMail' => Http::response('', 202),
    ]);

    $type = EmailType::factory()->registration()->create();
    $account = EmailAccount::factory()->outlookOAuth()->create([
        'from_email' => 'noreply@outlook.com',
    ]);
    $account->syncEmailTypes([$type->id]);

    $user = User::factory()->create(['email' => 'jane@example.com']);

    app(OutboundMailService::class)->sendForType(
        EmailType::CODE_REGISTRATION,
        new UserRegisteredMail($user),
        $user->email,
    );

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://graph.microsoft.com/v1.0/me/sendMail'
        && $request['message']['toRecipients'][0]['emailAddress']['address'] === 'jane@example.com');
});

test('a disabled email type assignment is not used', function () {
    Mail::fake();
    Http::preventStrayRequests();

    $type = EmailType::factory()->registration()->create();
    $account = EmailAccount::factory()->create(['from_email' => 'disabled@example.com']);
    $account->emailTypes()->attach($type->id, ['is_enabled' => false]);

    $user = User::factory()->create(['email' => 'jane@example.com']);

    app(OutboundMailService::class)->sendForType(
        EmailType::CODE_REGISTRATION,
        new UserRegisteredMail($user),
        $user->email,
    );

    Mail::assertNothingSent();
});

test('gmail send failure is reported', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response(['error' => 'failed'], 500),
    ]);

    $type = EmailType::factory()->registration()->create();
    $account = EmailAccount::factory()->gmailOAuth()->create();
    $account->syncEmailTypes([$type->id]);

    $user = User::factory()->create(['email' => 'jane@example.com']);

    app(OutboundMailService::class)->sendForType(
        EmailType::CODE_REGISTRATION,
        new UserRegisteredMail($user),
        $user->email,
    );
})->throws(EmailProviderException::class);
