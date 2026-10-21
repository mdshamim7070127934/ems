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
        Schema::create('mst_editor_setting', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique('code');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_editor_setting');
    }
};
