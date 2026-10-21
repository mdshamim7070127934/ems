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
        Schema::create('mst_email_type', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_email_type');
    }
};
