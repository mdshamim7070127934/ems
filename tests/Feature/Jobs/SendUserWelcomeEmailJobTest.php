<?php

use App\Jobs\SendUserWelcomeEmailJob;
use App\Mail\UserRegisteredMail;
use App\Models\Master\EmailAccount;
use App\Models\Master\EmailType;
use App\Models\User;
use App\Services\Mail\OutboundMailService;
use Illuminate\Support\Facades\Mail;

test('the welcome email job sends mail through the registration email account', function () {
    Mail::fake();

    $type = EmailType::factory()->registration()->create();
    $account = EmailAccount::factory()->create([
        'from_name' => 'EMS Mail',
        'from_email' => 'noreply@example.com',
    ]);
    $account->syncEmailTypes([$type->id]);

    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'first_name' => 'Jane',
        'name' => 'Jane Doe',
    ]);

    (new SendUserWelcomeEmailJob($user))->handle(app(OutboundMailService::class));

    Mail::assertSent(
        UserRegisteredMail::class,
        fn (UserRegisteredMail $mail): bool => $mail->hasTo('jane@example.com')
            && collect($mail->from)->contains(
                fn (array $from): bool => ($from['address'] ?? null) === 'noreply@example.com',
            ),
    );
});
