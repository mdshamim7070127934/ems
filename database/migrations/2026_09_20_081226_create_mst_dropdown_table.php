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
        Schema::create('mst_dropdown', function (Blueprint $table) {
            $table->integer('option_type_id')->autoIncrement();
            $table->integer('module_id');
            $table->string('name', 100);
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->dateTime('created_on')->useCurrent();
            $table->dateTime('updated_on')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['module_id', 'name']);
            $table->foreign('module_id')
                ->references('module_id')
                ->on('mst_module')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_dropdown');
    }
};
