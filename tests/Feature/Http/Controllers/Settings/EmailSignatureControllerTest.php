<?php

use App\Enums\Master\Status;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailSignature;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('email-signatures.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view only their signatures', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->travelTo('2026-09-21 10:00:00');

    EmailSignature::factory()->create([
        'user_id' => $user->id,
        'signature_name' => 'Work',
        'signature_html' => '<p>Work</p>',
        'is_default' => true,
    ]);
    EmailSignature::factory()->create([
        'user_id' => $other->id,
        'signature_name' => 'Personal',
    ]);

    $this->actingAs($user)
        ->get(route('email-signatures.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/email-signatures/index')
            ->has('signatures.data', 1)
            ->where('signatures.data.0.signature_name', 'Work')
            ->where('signatures.data.0.is_default', true)
            ->where('signatures.data.0.status', 'active')
            ->where('signatures.data.0.created_at', '21-09-2026')
            ->where('filters.sortBy', 'signature_name')
            ->where('filters.pageSize', 10)
        );
});

test('authenticated users can create a signature', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('email-signatures.index'))
        ->post(route('email-signatures.store'), [
            'signature_name' => 'Office',
            'signature_html' => '<p>Thanks</p>',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('email-signatures.index'));

    $signature = EmailSignature::query()->where('user_id', $user->id)->first();

    expect($signature)->not->toBeNull()
        ->and($signature?->signature_name)->toBe('Office')
        ->and($signature?->signature_html)->toBe('<p>Thanks</p>')
        ->and($signature?->status)->toBe(Status::Active)
        ->and($signature?->is_default)->toBeFalse();
});

test('signature name is required when creating', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('email-signatures.index'))
        ->post(route('email-signatures.store'), [
            'signature_name' => '',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['signature_name' => 'The signature name field is required.'])
        ->assertRedirect(route('email-signatures.index'));
});

test('signature name must be unique per user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    EmailSignature::factory()->create([
        'user_id' => $user->id,
        'signature_name' => 'Work',
    ]);
    EmailSignature::factory()->create([
        'user_id' => $other->id,
        'signature_name' => 'Work',
    ]);

    $this->actingAs($user)
        ->from(route('email-signatures.index'))
        ->post(route('email-signatures.store'), [
            'signature_name' => 'Work',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('signature_name')
        ->assertRedirect(route('email-signatures.index'));

    expect(EmailSignature::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('authenticated users can update a signature', function () {
    $user = User::factory()->create();
    $signature = EmailSignature::factory()->create([
        'user_id' => $user->id,
        'signature_name' => 'Work',
        'signature_html' => '<p>Old</p>',
    ]);

    $this->actingAs($user)
        ->from(route('email-signatures.index'))
        ->patch(route('email-signatures.update', $signature), [
            'signature_name' => 'Office',
            'signature_html' => '<p>New</p>',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('email-signatures.index'));

    $signature->refresh();

    expect($signature->signature_name)->toBe('Office')
        ->and($signature->signature_html)->toBe('<p>New</p>');
});

test('inactivating a default signature clears the default flag', function () {
    $user = User::factory()->create();
    $signature = EmailSignature::factory()->default()->create([
        'user_id' => $user->id,
        'signature_name' => 'Work',
    ]);

    $this->actingAs($user)
        ->from(route('email-signatures.index'))
        ->patch(route('email-signatures.update', $signature), [
            'signature_name' => 'Work',
            'signature_html' => $signature->signature_html,
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('email-signatures.index'));

    expect($signature->fresh()?->status)->toBe(Status::Inactive)
        ->and($signature->fresh()?->is_default)->toBeFalse();
});

test('setting a default signature clears the previous default for that user', function () {
    $user = User::factory()->create();
    $work = EmailSignature::factory()->default()->create([
        'user_id' => $user->id,
        'signature_name' => 'Work',
    ]);
    $personal = EmailSignature::factory()->create([
        'user_id' => $user->id,
        'signature_name' => 'Personal',
    ]);
    $otherDefault = EmailSignature::factory()->default()->create([
        'signature_name' => 'Other default',
    ]);

    $this->actingAs($user)
        ->from(route('email-signatures.index'))
        ->post(route('email-signatures.make-default', $personal))
        ->assertRedirect(route('email-signatures.index'));

    expect($personal->fresh()?->is_default)->toBeTrue()
        ->and($work->fresh()?->is_default)->toBeFalse()
        ->and($otherDefault->fresh()?->is_default)->toBeTrue();
});

test('an inactive signature cannot be set as the default', function () {
    $user = User::factory()->create();
    $active = EmailSignature::factory()->default()->create([
        'user_id' => $user->id,
        'signature_name' => 'Work',
    ]);
    $inactive = EmailSignature::factory()->inactive()->create([
        'user_id' => $user->id,
        'signature_name' => 'Old',
    ]);

    $this->actingAs($user)
        ->from(route('email-signatures.index'))
        ->post(route('email-signatures.make-default', $inactive))
        ->assertRedirect(route('email-signatures.index'));

    expect($inactive->fresh()?->is_default)->toBeFalse()
        ->and($active->fresh()?->is_default)->toBeTrue();
});

test('a user can unset their default signature', function () {
    $user = User::factory()->create();
    $signature = EmailSignature::factory()->default()->create([
        'user_id' => $user->id,
        'signature_name' => 'Work',
    ]);

    $this->actingAs($user)
        ->from(route('email-signatures.index'))
        ->post(route('email-signatures.make-default', $signature), [
            'unset' => true,
        ])
        ->assertRedirect(route('email-signatures.index'));

    expect($signature->fresh()?->is_default)->toBeFalse();
});

test('a user can delete their signature', function () {
    $user = User::factory()->create();
    $signature = EmailSignature::factory()->create([
        'user_id' => $user->id,
        'signature_name' => 'Work',
    ]);

    $this->actingAs($user)
        ->from(route('email-signatures.index'))
        ->delete(route('email-signatures.destroy', $signature))
        ->assertRedirect(route('email-signatures.index'));

    $this->assertModelMissing($signature);
});

test('a user cannot update another users signature', function () {
    $user = User::factory()->create();
    $signature = EmailSignature::factory()->create([
        'signature_name' => 'Private',
    ]);

    $this->actingAs($user)
        ->patch(route('email-signatures.update', $signature), [
            'signature_name' => 'Stolen',
            'status' => 'active',
        ])
        ->assertForbidden();

    expect($signature->fresh()?->signature_name)->toBe('Private');
});

test('a user cannot delete another users signature', function () {
    $user = User::factory()->create();
    $signature = EmailSignature::factory()->create([
        'signature_name' => 'Private',
    ]);

    $this->actingAs($user)
        ->delete(route('email-signatures.destroy', $signature))
        ->assertForbidden();

    $this->assertModelExists($signature);
});

test('a user cannot set another users signature as default', function () {
    $user = User::factory()->create();
    $signature = EmailSignature::factory()->create([
        'signature_name' => 'Private',
    ]);

    $this->actingAs($user)
        ->post(route('email-signatures.make-default', $signature))
        ->assertForbidden();

    expect($signature->fresh()?->is_default)->toBeFalse();
});

test('signatures can be searched by name', function () {
    $user = User::factory()->create();
    EmailSignature::factory()->create([
        'user_id' => $user->id,
        'signature_name' => 'Work',
    ]);
    EmailSignature::factory()->create([
        'user_id' => $user->id,
        'signature_name' => 'Personal',
    ]);

    $this->actingAs($user)
        ->get(route('email-signatures.index', ['search' => 'pers']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('signatures.data', 1)
            ->where('signatures.data.0.signature_name', 'Personal')
            ->where('filters.search', 'pers')
        );
});

test('mailbox pages receive the users active signatures', function () {
    $user = User::factory()->create();
    EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    EmailSignature::factory()->default()->create([
        'user_id' => $user->id,
        'signature_name' => 'Work',
        'signature_html' => '<p>Regards</p>',
    ]);
    EmailSignature::factory()->inactive()->create([
        'user_id' => $user->id,
        'signature_name' => 'Old',
        'signature_html' => '<p>Old</p>',
    ]);
    EmailSignature::factory()->create([
        'signature_name' => 'Someone else',
        'signature_html' => '<p>Nope</p>',
        'is_default' => true,
    ]);

    $this->actingAs($user)
        ->get(route('mailbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('mailbox.signatures.items', 1)
            ->where('mailbox.signatures.items.0.name', 'Work')
            ->where('mailbox.signatures.items.0.html', '<p>Regards</p>')
            ->where('mailbox.signatures.default_html', '<p>Regards</p>')
        );
});
