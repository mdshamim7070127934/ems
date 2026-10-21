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
        Schema::create('mst_branch', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('branch_name', 100);
            $table->boolean('geofencing')->default(false);
            $table->string('geofencing_range', 50)->nullable();
            $table->string('branch_country_code', 255)->nullable();
            $table->string('branch_fbcountry_code', 100);
            $table->unsignedBigInteger('branch_manager');
            $table->integer('country_id');
            $table->integer('region_id');
            $table->integer('group_id');
            $table->string('branch_code', 50);
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->integer('man_power')->nullable();
            $table->integer('retail_man_power')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('last_modified_by');
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->dateTime('created_on')->useCurrent();
            $table->dateTime('updated_on')->useCurrent()->useCurrentOnUpdate();

            $table->unique('branch_code');
            $table->index('country_id');
            $table->index('region_id');
            $table->index('group_id');
            $table->foreign('branch_manager')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('country_id')
                ->references('id')
                ->on('mst_country')
                ->restrictOnDelete();
            $table->foreign('region_id')
                ->references('id')
                ->on('mst_region')
                ->restrictOnDelete();
            $table->foreign('group_id')
                ->references('id')
                ->on('mst_group')
                ->restrictOnDelete();
            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('last_modified_by')
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
        Schema::dropIfExists('mst_branch');
    }
};
