<?php

use App\Http\Controllers\Mailbox\MailboxAccountController;
use App\Http\Controllers\Mailbox\MailboxAttachmentController;
use App\Http\Controllers\Mailbox\MailboxDraftController;
use App\Http\Controllers\Mailbox\MailboxInboxController;
use App\Http\Controllers\Mailbox\MailboxLabelController;
use App\Http\Controllers\Mailbox\MailboxSyncController;
use App\Http\Controllers\Mailbox\MailboxThreadController;
use App\Http\Controllers\Master\BranchController;
use App\Http\Controllers\Master\CityController;
use App\Http\Controllers\Master\CountryController;
use App\Http\Controllers\Master\DropdownController;
use App\Http\Controllers\Master\DropdownOptionValueController;
use App\Http\Controllers\Master\EditorSettingController;
use App\Http\Controllers\Master\EmailAccountController;
use App\Http\Controllers\Master\EmailAccountOAuthController;
use App\Http\Controllers\Master\EmailTypeController;
use App\Http\Controllers\Master\GroupController;
use App\Http\Controllers\Master\MenuController;
use App\Http\Controllers\Master\ModuleController;
use App\Http\Controllers\Master\RegionController;
use App\Http\Controllers\Master\RoleController;
use App\Http\Controllers\Master\StateController;
use App\Http\Controllers\Master\TimezoneController;
use App\Http\Controllers\Master\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('modules', ModuleController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('dropdowns', DropdownController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('dropdown-option-values', DropdownOptionValueController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('menus', MenuController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('countries', CountryController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('states', StateController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('cities', CityController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('timezones', TimezoneController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('groups', GroupController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('regions', RegionController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('branches', BranchController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('roles', RoleController::class);
        Route::resource('users', UserController::class);
        Route::resource('email-types', EmailTypeController::class)
            ->except(['create', 'edit', 'show']);
        Route::post('editor-settings/{editor_setting}/default', [EditorSettingController::class, 'makeDefault'])
            ->name('editor-settings.make-default');
        Route::resource('editor-settings', EditorSettingController::class)
            ->except(['create', 'edit', 'show', 'store']);

        Route::get('email-accounts/connect', [EmailAccountOAuthController::class, 'create'])
            ->name('email-accounts.connect');
        Route::get('email-accounts/oauth/{provider}/redirect', [EmailAccountOAuthController::class, 'redirect'])
            ->whereIn('provider', ['gmail', 'outlook'])
            ->middleware('throttle:10,1')
            ->withoutMiddleware('verified')
            ->name('email-accounts.oauth.redirect');
        Route::get('email-accounts/oauth/{provider}/callback', [EmailAccountOAuthController::class, 'callback'])
            ->whereIn('provider', ['gmail', 'outlook'])
            ->withoutMiddleware('verified')
            ->name('email-accounts.oauth.callback');
        Route::get('email-accounts/{email_account}/reconnect', [EmailAccountOAuthController::class, 'reconnect'])
            ->middleware('throttle:10,1')
            ->withoutMiddleware('verified')
            ->name('email-accounts.reconnect');
        Route::post('email-accounts/{email_account}/disconnect', [EmailAccountOAuthController::class, 'disconnect'])
            ->name('email-accounts.disconnect');
        Route::resource('email-accounts', EmailAccountController::class);
    });

    Route::prefix('mailbox')->name('mailbox.')->group(function () {
        Route::get('/', [MailboxInboxController::class, 'index'])->name('index');
        Route::post('sync', [MailboxSyncController::class, 'store'])->name('sync');
        Route::post('block-sender', [MailboxInboxController::class, 'block'])->name('block-sender');
        Route::post('unblock-sender', [MailboxInboxController::class, 'unblock'])->name('unblock-sender');
        Route::post('action', [MailboxThreadController::class, 'action'])->name('action');
        Route::post('bulk-move', [MailboxThreadController::class, 'bulkMove'])->name('bulk-move');

        Route::get('connect', [MailboxAccountController::class, 'connect'])->name('connect');
        Route::get('connect/smtp', [MailboxAccountController::class, 'createSmtp'])->name('connect.smtp');
        Route::post('connect/smtp', [MailboxAccountController::class, 'storeSmtp'])->name('connect.smtp.store');
        Route::get('oauth/{provider}/redirect', [MailboxAccountController::class, 'redirect'])
            ->whereIn('provider', ['gmail', 'outlook'])
            ->middleware('throttle:10,1')
            ->withoutMiddleware('verified')
            ->name('oauth.redirect');
        Route::get('oauth/{provider}/callback', [MailboxAccountController::class, 'callback'])
            ->whereIn('provider', ['gmail', 'outlook'])
            ->withoutMiddleware('verified')
            ->name('oauth.callback');
        Route::get('accounts/{mailbox_account}/reconnect', [MailboxAccountController::class, 'reconnect'])
            ->middleware('throttle:10,1')
            ->withoutMiddleware('verified')
            ->name('reconnect');
        Route::post('accounts/{mailbox_account}/disconnect', [MailboxAccountController::class, 'disconnect'])
            ->name('disconnect');
        Route::post('accounts/{mailbox_account}/default', [MailboxAccountController::class, 'makeDefault'])
            ->name('accounts.default');
        Route::delete('accounts/{mailbox_account}', [MailboxAccountController::class, 'destroy'])
            ->name('accounts.destroy');

        Route::get('threads/{mailbox_thread}', [MailboxThreadController::class, 'show'])->name('threads.show');
        Route::get('attachments/{mailbox_attachment}/preview', [MailboxAttachmentController::class, 'preview'])->name('attachments.preview');
        Route::get('attachments/{mailbox_attachment}', [MailboxAttachmentController::class, 'show'])->name('attachments.show');
        Route::get('autocomplete', [MailboxAttachmentController::class, 'autocomplete'])->name('autocomplete');

        Route::get('drafts', [MailboxDraftController::class, 'index'])->name('drafts');
        Route::get('drafts/{mailbox_draft}', [MailboxDraftController::class, 'show'])->name('drafts.show');
        Route::get('compose/prefill', [MailboxDraftController::class, 'prefill'])->name('compose.prefill');
        Route::get('compose', [MailboxDraftController::class, 'create'])->name('compose');
        Route::post('drafts', [MailboxDraftController::class, 'store'])->name('drafts.store');
        Route::post('drafts/{mailbox_draft}/send', [MailboxDraftController::class, 'send'])->name('drafts.send');
        Route::delete('drafts/{mailbox_draft}', [MailboxDraftController::class, 'destroy'])->name('drafts.destroy');

        Route::post('labels', [MailboxLabelController::class, 'store'])->name('labels.store');
        Route::patch('labels/{mailbox_label}', [MailboxLabelController::class, 'update'])->name('labels.update');
        Route::post('labels/assign', [MailboxLabelController::class, 'assign'])->name('labels.assign');
        Route::post('labels/remove', [MailboxLabelController::class, 'remove'])->name('labels.remove');
        Route::post('labels/assign-threads', [MailboxLabelController::class, 'assignToThreads'])->name('labels.assign-threads');
        Route::delete('labels/{mailbox_label}', [MailboxLabelController::class, 'destroy'])->name('labels.destroy');
    });
});

require __DIR__.'/settings.php';
