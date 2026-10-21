<?php

use App\Enums\Master\EmailMailer;
use App\Enums\Master\EmailProvider;
use App\Enums\Master\SmtpEncryption;
use App\Enums\Master\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mst_email_account', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name', 150);
            $table->enum('provider', EmailProvider::cases());
            $table->enum('mailer', EmailMailer::cases())->default(EmailMailer::Smtp);
            $table->string('from_name', 150);
            $table->string('from_email', 255);
            $table->string('username', 255)->nullable();
            $table->text('password')->nullable();
            $table->string('smtp_host', 255)->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->enum('smtp_encryption', SmtpEncryption::cases())->nullable();
            $table->string('oauth_client_id', 255)->nullable();
            $table->text('oauth_client_secret')->nullable();
            $table->text('oauth_access_token')->nullable();
            $table->text('oauth_refresh_token')->nullable();
            $table->dateTime('oauth_token_expires_at')->nullable();
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique('name');
            $table->index('status');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_email_account');
    }
};
