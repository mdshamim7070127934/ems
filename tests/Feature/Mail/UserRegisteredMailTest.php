<?php

use App\Mail\UserRegisteredMail;
use App\Models\User;

test('the welcome email includes the login url and does not include the password', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'SecretPass123!',
    ]);

    $mail = new UserRegisteredMail($user);

    $mail->assertSeeInHtml('Jane')
        ->assertSeeInHtml(route('login'))
        ->assertDontSeeInHtml('SecretPass123!');
});
