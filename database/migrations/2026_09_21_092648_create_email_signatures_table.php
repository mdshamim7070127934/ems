<?php

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
        Schema::create('email_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('signature_name');
            $table->longText('signature_html')->nullable();
            $table->boolean('is_default')->default(false);
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->timestamps();

            $table->unique(['user_id', 'signature_name']);
            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_signatures');
    }
};
