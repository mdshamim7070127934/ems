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
        Schema::create('mst_timezone', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name', 100);
            $table->string('code', 100);
            $table->string('utc_offset', 10);
            $table->string('display_name', 150);
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->dateTime('created_on')->useCurrent();
            $table->dateTime('updated_on')->useCurrent()->useCurrentOnUpdate();

            $table->unique('name');
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_timezone');
    }
};
