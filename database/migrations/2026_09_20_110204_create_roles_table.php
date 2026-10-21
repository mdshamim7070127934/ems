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
        Schema::create('roles', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('role_name', 100);
            $table->string('display_name', 100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super_admin')->default(false)->comment('1=bypass all menu checks');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('role_name', 'uniq_role_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
