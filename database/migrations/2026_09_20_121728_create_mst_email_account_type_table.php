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
        Schema::create('mst_email_account_type', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('email_account_id');
            $table->integer('email_type_id');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['email_account_id', 'email_type_id'], 'uniq_email_account_type');
            $table->foreign('email_account_id')
                ->references('id')
                ->on('mst_email_account')
                ->cascadeOnDelete();
            $table->foreign('email_type_id')
                ->references('id')
                ->on('mst_email_type')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_email_account_type');
    }
};
