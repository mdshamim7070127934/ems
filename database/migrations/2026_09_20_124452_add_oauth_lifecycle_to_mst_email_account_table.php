<?php

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
        Schema::table('mst_email_account', function (Blueprint $table) {
            $table->string('oauth_provider_account_id', 255)->nullable()->after('from_email');
            $table->dateTime('last_token_refresh_at')->nullable()->after('oauth_token_expires_at');
            $table->boolean('reauthorization_required')->default(false)->after('last_token_refresh_at');
            $table->string('oauth_error', 255)->nullable()->after('reauthorization_required');

            $table->unique(['provider', 'from_email'], 'mst_email_account_provider_from_email_unique');
            $table->unique(['provider', 'oauth_provider_account_id'], 'mst_email_account_provider_account_id_unique');
            $table->index(['provider', 'reauthorization_required'], 'mst_email_account_provider_reauth_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_email_account', function (Blueprint $table) {
            $table->dropUnique('mst_email_account_provider_from_email_unique');
            $table->dropUnique('mst_email_account_provider_account_id_unique');
            $table->dropIndex('mst_email_account_provider_reauth_index');
            $table->dropColumn([
                'oauth_provider_account_id',
                'last_token_refresh_at',
                'reauthorization_required',
                'oauth_error',
            ]);
        });
    }
};
