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
        Schema::create('mst_group', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('group_name', 250);
            $table->text('description');
            $table->unsignedBigInteger('owner_id');
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('group_name');
            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_group');
    }
};
