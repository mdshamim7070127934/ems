<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->enum('provider', ['gmail', 'outlook', 'smtp_imap']);
            $table->string('email');
            $table->string('display_name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->dateTime('token_expiry')->nullable();
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_encryption', 20)->nullable();
            $table->string('imap_host')->nullable();
            $table->integer('imap_port')->nullable();
            $table->string('imap_username')->nullable();
            $table->text('imap_password')->nullable();
            $table->string('imap_encryption', 20)->nullable();
            $table->timestamps();

            $table->index('provider', 'idx_email_accounts_provider');
            $table->index('email', 'idx_email_accounts_email');
            $table->index('is_active', 'idx_email_accounts_is_active');
        });

        Schema::create('email_account_sync_state', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->primary();
            $table->string('gmail_history_id', 64)->nullable();
            $table->text('outlook_delta_inbox')->nullable();
            $table->text('outlook_delta_sent')->nullable();
            $table->dateTime('imap_last_sync_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('account_id', 'fk_sync_state_account')
                ->references('id')
                ->on('email_accounts')
                ->cascadeOnDelete();
        });

        Schema::create('email_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->text('provider_folder_id')->nullable();
            $table->string('name');
            $table->string('slug', 100);
            $table->string('icon', 50)->nullable();
            $table->enum('type', ['system', 'custom'])->default('custom');
            $table->integer('message_count')->default(0);
            $table->integer('unread_count')->default(0);
            $table->timestamps();

            $table->unique(['account_id', 'slug'], 'uq_account_slug');
        });

        Schema::create('email_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->string('thread_id');
            $table->string('subject', 500)->nullable();
            $table->string('snippet', 500)->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->boolean('is_starred')->default(false);
            $table->timestamps();

            $table->unique(['account_id', 'thread_id'], 'uq_account_thread');
            $table->index('last_message_at', 'idx_threads_last_message_at');
            $table->index('is_starred', 'idx_threads_is_starred');
        });

        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->foreignId('thread_id')->nullable()->constrained('email_threads')->nullOnDelete();
            $table->string('message_id', 500);
            $table->string('provider_message_id', 500)->nullable();
            $table->foreignId('folder_id')->nullable()->constrained('email_folders')->nullOnDelete();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->text('to_emails')->nullable();
            $table->text('cc_emails')->nullable();
            $table->text('bcc_emails')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('subject', 1000)->default('');
            $table->string('snippet', 500)->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_plain')->nullable();
            $table->dateTime('internal_date')->nullable();
            $table->boolean('is_unread')->default(true);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->string('in_reply_to', 500)->nullable();
            $table->text('references')->nullable();
            $table->timestamps();

            $table->index('internal_date', 'idx_emails_internal_date');
            $table->index('is_unread', 'idx_emails_is_unread');
            $table->index('is_starred', 'idx_emails_is_starred');
            $table->index('from_email', 'idx_emails_from_email');
        });

        $this->addEmailsUniqueAndFullText();

        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->string('storage_path', 500);
            $table->string('content_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('email_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->foreignId('thread_id')->nullable()->constrained('email_threads')->nullOnDelete();
            $table->string('in_reply_to', 500)->nullable();
            $table->text('to_emails')->nullable();
            $table->text('cc_emails')->nullable();
            $table->text('bcc_emails')->nullable();
            $table->string('subject', 1000)->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_plain')->nullable();
            $table->timestamps();
        });

        Schema::create('draft_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->constrained('email_drafts')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->string('storage_path', 500);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('email_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('email_accounts')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('color', 20)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['account_id', 'slug'], 'uq_label_account_slug');
        });

        Schema::create('email_label_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('email_labels')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['email_id', 'label_id'], 'uq_email_label');
        });

        Schema::create('ems_block_email', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email_id', 100);
            $table->boolean('is_block')->default(true);
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'email_id'], 'uniq_user_email');
        });

        Schema::create('ems_unique_email', function (Blueprint $table) {
            $table->id();
            $table->string('email_id', 100);
            $table->timestamp('created_at')->nullable();

            $table->unique('email_id', 'uq_unique_email_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ems_unique_email');
        Schema::dropIfExists('ems_block_email');
        Schema::dropIfExists('email_label_mappings');
        Schema::dropIfExists('email_labels');
        Schema::dropIfExists('draft_attachments');
        Schema::dropIfExists('email_drafts');
        Schema::dropIfExists('email_attachments');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('email_threads');
        Schema::dropIfExists('email_folders');
        Schema::dropIfExists('email_account_sync_state');
        Schema::dropIfExists('email_accounts');
    }

    private function addEmailsUniqueAndFullText(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `emails` ADD UNIQUE INDEX `uq_account_message_id` (`account_id`, `message_id`(191))');
            DB::statement('ALTER TABLE `emails` ADD FULLTEXT INDEX `ft_search` (`subject`, `body_plain`, `from_email`, `to_emails`)');

            return;
        }

        Schema::table('emails', function (Blueprint $table) {
            $table->unique(['account_id', 'message_id'], 'uq_account_message_id');
        });
    }
};
