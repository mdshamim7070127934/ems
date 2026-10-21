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
        Schema::create('mst_module', function (Blueprint $table) {
            $table->integer('module_id')->autoIncrement();
            $table->string('module_name', 100)->unique();
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->dateTime('created_on')->useCurrent();
            $table->dateTime('updated_on')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_module');
    }
};
