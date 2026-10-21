<?php

use App\Enums\Master\EditorDriver;
use App\Enums\Master\Status;
use App\Models\Mailbox\EmailAccount;
use App\Models\Master\EditorSetting;
use App\Models\User;
use App\Services\Master\EditorSettingService;
use Database\Seeders\EditorSettingSeeder;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Cache::forget(EditorSettingService::CACHE_KEY);
});

test('guests are redirected to the login page', function () {
    $this->get(route('master.editor-settings.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the editor setting list', function () {
    $user = User::factory()->create();

    $this->travelTo('2026-09-21 10:00:00');

    EditorSetting::factory()->tiptap()->default()->create();

    $this->actingAs($user)
        ->get(route('master.editor-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('master/editor-settings/index')
            ->has('editorSettings.data', 1)
            ->where('editorSettings.data.0.code', 'tiptap')
            ->where('editorSettings.data.0.name', 'Tiptap')
            ->where('editorSettings.data.0.is_default', true)
            ->where('editorSettings.data.0.status', 'active')
            ->where('editorSettings.data.0.created_at', '21-09-2026')
            ->where('filters.sortBy', 'sort_order')
            ->where('filters.pageSize', 10)
        );
});

test('authenticated users can update an editor setting', function () {
    $user = User::factory()->create();
    $setting = EditorSetting::factory()->ckeditor()->create([
        'name' => 'CKEditor',
    ]);

    $this->actingAs($user)
        ->from(route('master.editor-settings.index'))
        ->put(route('master.editor-settings.update', $setting), [
            'name' => 'CKEditor 4',
            'description' => 'Used for compose.',
            'sort_order' => 4,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.editor-settings.index'));

    $setting->refresh();

    expect($setting->code)->toBe(EditorDriver::Ckeditor)
        ->and($setting->name)->toBe('CKEditor 4')
        ->and($setting->sort_order)->toBe(4);
});

test('editor setting name is required when updating', function () {
    $user = User::factory()->create();
    $setting = EditorSetting::factory()->tiptap()->create();

    $this->actingAs($user)
        ->from(route('master.editor-settings.index'))
        ->put(route('master.editor-settings.update', $setting), [
            'name' => '',
            'sort_order' => 1,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name' => 'The name field is required.'])
        ->assertRedirect(route('master.editor-settings.index'));
});

test('the default editor cannot be inactivated', function () {
    $user = User::factory()->create();
    $setting = EditorSetting::factory()->tiptap()->default()->create();

    $this->actingAs($user)
        ->from(route('master.editor-settings.index'))
        ->put(route('master.editor-settings.update', $setting), [
            'name' => 'Tiptap',
            'sort_order' => 1,
            'status' => 'inactive',
        ])
        ->assertSessionHasErrors('status')
        ->assertRedirect(route('master.editor-settings.index'));

    expect($setting->fresh()?->status)->toBe(Status::Active)
        ->and($setting->fresh()?->is_default)->toBeTrue();
});

test('setting a default editor clears the previous default', function () {
    $user = User::factory()->create();
    $tiptap = EditorSetting::factory()->tiptap()->default()->create();
    $ckeditor = EditorSetting::factory()->ckeditor()->create();

    $this->actingAs($user)
        ->from(route('master.editor-settings.index'))
        ->post(route('master.editor-settings.make-default', $ckeditor))
        ->assertRedirect(route('master.editor-settings.index'));

    expect($ckeditor->fresh()?->is_default)->toBeTrue()
        ->and($tiptap->fresh()?->is_default)->toBeFalse();
});

test('an inactive editor cannot be set as the default', function () {
    $user = User::factory()->create();
    $tiptap = EditorSetting::factory()->tiptap()->default()->create();
    $ckeditor = EditorSetting::factory()->ckeditor()->inactive()->create();

    $this->actingAs($user)
        ->from(route('master.editor-settings.index'))
        ->post(route('master.editor-settings.make-default', $ckeditor))
        ->assertRedirect(route('master.editor-settings.index'));

    expect($ckeditor->fresh()?->is_default)->toBeFalse()
        ->and($tiptap->fresh()?->is_default)->toBeTrue();
});

test('the default editor cannot be deleted', function () {
    $user = User::factory()->create();
    $setting = EditorSetting::factory()->tiptap()->default()->create();

    $this->actingAs($user)
        ->from(route('master.editor-settings.index'))
        ->delete(route('master.editor-settings.destroy', $setting))
        ->assertRedirect(route('master.editor-settings.index'));

    $this->assertModelExists($setting);
});

test('a non default editor can be deleted', function () {
    $user = User::factory()->create();
    EditorSetting::factory()->tiptap()->default()->create();
    $setting = EditorSetting::factory()->tinymce()->create();

    $this->actingAs($user)
        ->from(route('master.editor-settings.index'))
        ->delete(route('master.editor-settings.destroy', $setting))
        ->assertRedirect(route('master.editor-settings.index'));

    $this->assertModelMissing($setting);
});

test('editor settings can be searched by code', function () {
    $user = User::factory()->create();
    EditorSetting::factory()->tiptap()->create();
    EditorSetting::factory()->ckeditor()->create();

    $this->actingAs($user)
        ->get(route('master.editor-settings.index', ['search' => 'cked']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('editorSettings.data', 1)
            ->where('editorSettings.data.0.code', 'ckeditor')
            ->where('filters.search', 'cked')
        );
});

test('the editor setting seeder is idempotent', function () {
    $this->seed(EditorSettingSeeder::class);
    $this->seed(EditorSettingSeeder::class);

    expect(EditorSetting::query()->count())->toBe(3)
        ->and(EditorSetting::query()->where('code', EditorDriver::Tiptap)->where('is_default', true)->count())->toBe(1);
});

test('mailbox pages receive the active editor code', function () {
    $user = User::factory()->create();
    EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);
    EditorSetting::factory()->ckeditor()->default()->create();

    Cache::forget(EditorSettingService::CACHE_KEY);

    $this->actingAs($user)
        ->get(route('mailbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('mailbox.editor', 'ckeditor')
        );
});

test('mailbox pages fall back to tiptap when no default editor is configured', function () {
    $user = User::factory()->create();
    EmailAccount::factory()->gmail()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('mailbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('mailbox.editor', 'tiptap')
        );
});
